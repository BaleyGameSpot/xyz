package com.chinarsignals.app.ui.auth

import android.content.Intent
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import com.chinarsignals.app.R
import com.chinarsignals.app.databinding.FragmentSignupBinding
import com.chinarsignals.app.ui.main.MainActivity
import com.chinarsignals.app.utils.Resource
import com.chinarsignals.app.utils.gone
import com.chinarsignals.app.utils.snackbarError
import com.chinarsignals.app.utils.visible
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch

@AndroidEntryPoint
class SignupFragment : Fragment() {

    private var _binding: FragmentSignupBinding? = null
    private val binding get() = _binding!!

    private val viewModel: AuthViewModel by viewModels()

    override fun onCreateView(inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?): View {
        _binding = FragmentSignupBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        setupClickListeners()
        observeAuthState()
    }

    private fun setupClickListeners() {
        binding.btnCreateAccount.setOnClickListener {
            val name = binding.etName.text.toString().trim()
            val email = binding.etEmail.text.toString().trim()
            val password = binding.etPassword.text.toString().trim()
            val confirmPassword = binding.etConfirmPassword.text.toString().trim()
            if (validateInputs(name, email, password, confirmPassword)) {
                viewModel.register(name, email, password, confirmPassword)
            }
        }

        binding.tvLogin.setOnClickListener {
            findNavController().navigateUp()
        }
    }

    private fun validateInputs(name: String, email: String, password: String, confirmPassword: String): Boolean {
        var valid = true
        if (name.isEmpty()) {
            binding.tilName.error = "Name is required"
            valid = false
        } else {
            binding.tilName.error = null
        }
        if (email.isEmpty()) {
            binding.tilEmail.error = "Email is required"
            valid = false
        } else if (!android.util.Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
            binding.tilEmail.error = "Enter a valid email"
            valid = false
        } else {
            binding.tilEmail.error = null
        }
        if (password.isEmpty()) {
            binding.tilPassword.error = "Password is required"
            valid = false
        } else if (password.length < 8) {
            binding.tilPassword.error = "Password must be at least 8 characters"
            valid = false
        } else {
            binding.tilPassword.error = null
        }
        if (confirmPassword != password) {
            binding.tilConfirmPassword.error = "Passwords do not match"
            valid = false
        } else {
            binding.tilConfirmPassword.error = null
        }
        return valid
    }

    private fun observeAuthState() {
        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.authState.collectLatest { resource ->
                when (resource) {
                    is Resource.Loading -> {
                        binding.progressBar.visible()
                        binding.btnCreateAccount.isEnabled = false
                    }
                    is Resource.Success -> {
                        binding.progressBar.gone()
                        navigateToMain()
                    }
                    is Resource.Error -> {
                        binding.progressBar.gone()
                        binding.btnCreateAccount.isEnabled = true
                        binding.root.snackbarError(resource.message)
                        viewModel.resetState()
                    }
                    null -> {
                        binding.progressBar.gone()
                        binding.btnCreateAccount.isEnabled = true
                    }
                }
            }
        }
    }

    private fun navigateToMain() {
        startActivity(Intent(requireContext(), MainActivity::class.java))
        requireActivity().finish()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
