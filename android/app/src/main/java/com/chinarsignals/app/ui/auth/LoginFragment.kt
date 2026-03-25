package com.chinarsignals.app.ui.auth

import android.content.Intent
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.activity.result.contract.ActivityResultContracts
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import com.chinarsignals.app.BuildConfig
import com.chinarsignals.app.R
import com.chinarsignals.app.databinding.FragmentLoginBinding
import com.chinarsignals.app.ui.main.MainActivity
import com.chinarsignals.app.utils.Resource
import com.chinarsignals.app.utils.gone
import com.chinarsignals.app.utils.snackbarError
import com.chinarsignals.app.utils.toast
import com.chinarsignals.app.utils.visible
import com.google.android.gms.auth.api.signin.GoogleSignIn
import com.google.android.gms.auth.api.signin.GoogleSignInClient
import com.google.android.gms.auth.api.signin.GoogleSignInOptions
import com.google.android.gms.common.api.ApiException
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch

@AndroidEntryPoint
class LoginFragment : Fragment() {

    private var _binding: FragmentLoginBinding? = null
    private val binding get() = _binding!!

    private val viewModel: AuthViewModel by viewModels()
    private lateinit var googleSignInClient: GoogleSignInClient

    private val googleSignInLauncher = registerForActivityResult(
        ActivityResultContracts.StartActivityForResult()
    ) { result ->
        val task = GoogleSignIn.getSignedInAccountFromIntent(result.data)
        try {
            val account = task.getResult(ApiException::class.java)
            account?.let {
                viewModel.googleAuth(
                    idToken = it.idToken ?: "",
                    email = it.email ?: "",
                    name = it.displayName ?: "",
                    avatar = it.photoUrl?.toString()
                )
            }
        } catch (e: ApiException) {
            binding.root.snackbarError("Google Sign-In failed: ${e.message}")
        }
    }

    override fun onCreateView(inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?): View {
        _binding = FragmentLoginBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        setupGoogleSignIn()
        setupClickListeners()
        observeAuthState()
    }

    private fun setupGoogleSignIn() {
        val gso = GoogleSignInOptions.Builder(GoogleSignInOptions.DEFAULT_SIGN_IN)
            .requestIdToken(BuildConfig.GOOGLE_WEB_CLIENT_ID)
            .requestEmail()
            .requestProfile()
            .build()
        googleSignInClient = GoogleSignIn.getClient(requireActivity(), gso)
    }

    private fun setupClickListeners() {
        binding.btnSignIn.setOnClickListener {
            val email = binding.etEmail.text.toString().trim()
            val password = binding.etPassword.text.toString().trim()
            if (validateInputs(email, password)) {
                viewModel.login(email, password)
            }
        }

        binding.btnGoogleSignIn.setOnClickListener {
            googleSignInClient.signOut().addOnCompleteListener {
                googleSignInLauncher.launch(googleSignInClient.signInIntent)
            }
        }

        binding.tvSignUp.setOnClickListener {
            findNavController().navigate(R.id.action_loginFragment_to_signupFragment)
        }

        binding.tvForgotPassword.setOnClickListener {
            toast("Password reset feature coming soon")
        }
    }

    private fun validateInputs(email: String, password: String): Boolean {
        if (email.isEmpty()) {
            binding.tilEmail.error = "Email is required"
            return false
        }
        if (!android.util.Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
            binding.tilEmail.error = "Enter a valid email"
            return false
        }
        if (password.isEmpty()) {
            binding.tilPassword.error = "Password is required"
            return false
        }
        if (password.length < 6) {
            binding.tilPassword.error = "Password must be at least 6 characters"
            return false
        }
        binding.tilEmail.error = null
        binding.tilPassword.error = null
        return true
    }

    private fun observeAuthState() {
        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.authState.collectLatest { resource ->
                when (resource) {
                    is Resource.Loading -> {
                        binding.progressBar.visible()
                        binding.btnSignIn.isEnabled = false
                        binding.btnGoogleSignIn.isEnabled = false
                    }
                    is Resource.Success -> {
                        binding.progressBar.gone()
                        navigateToMain()
                    }
                    is Resource.Error -> {
                        binding.progressBar.gone()
                        binding.btnSignIn.isEnabled = true
                        binding.btnGoogleSignIn.isEnabled = true
                        binding.root.snackbarError(resource.message)
                        viewModel.resetState()
                    }
                    null -> {
                        binding.progressBar.gone()
                        binding.btnSignIn.isEnabled = true
                        binding.btnGoogleSignIn.isEnabled = true
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
