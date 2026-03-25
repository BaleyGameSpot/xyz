package com.chinarsignals.app.ui.main.profile

import android.content.Intent
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.lifecycleScope
import com.bumptech.glide.Glide
import com.chinarsignals.app.R
import com.chinarsignals.app.databinding.FragmentProfileBinding
import com.chinarsignals.app.ui.auth.AuthActivity
import com.chinarsignals.app.ui.packages.PackagesActivity
import com.chinarsignals.app.utils.Resource
import com.chinarsignals.app.utils.gone
import com.chinarsignals.app.utils.toDisplayDate
import com.chinarsignals.app.utils.visible
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch

@AndroidEntryPoint
class ProfileFragment : Fragment() {

    private var _binding: FragmentProfileBinding? = null
    private val binding get() = _binding!!

    private val viewModel: ProfileViewModel by viewModels()

    override fun onCreateView(inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?): View {
        _binding = FragmentProfileBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        loadCachedUser()
        setupClickListeners()
        observeData()
    }

    private fun loadCachedUser() {
        val user = viewModel.getCachedUser() ?: return
        binding.tvUserName.text = user.name
        binding.tvUserEmail.text = user.email
        if (!user.avatar.isNullOrBlank()) {
            Glide.with(this)
                .load(user.avatar)
                .placeholder(R.drawable.ic_avatar_placeholder)
                .circleCrop()
                .into(binding.ivAvatar)
        }
    }

    private fun setupClickListeners() {
        binding.btnUpgradePlan.setOnClickListener {
            startActivity(Intent(requireContext(), PackagesActivity::class.java))
        }

        binding.btnLogout.setOnClickListener {
            showLogoutConfirmation()
        }
    }

    private fun showLogoutConfirmation() {
        androidx.appcompat.app.AlertDialog.Builder(requireContext())
            .setTitle("Logout")
            .setMessage("Are you sure you want to logout?")
            .setPositiveButton("Logout") { _, _ -> viewModel.logout() }
            .setNegativeButton("Cancel", null)
            .show()
    }

    private fun observeData() {
        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.userState.collectLatest { resource ->
                if (resource is Resource.Success) {
                    val user = resource.data
                    binding.tvUserName.text = user.name
                    binding.tvUserEmail.text = user.email
                    if (!user.avatar.isNullOrBlank()) {
                        Glide.with(this@ProfileFragment)
                            .load(user.avatar)
                            .placeholder(R.drawable.ic_avatar_placeholder)
                            .circleCrop()
                            .into(binding.ivAvatar)
                    }
                }
            }
        }

        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.subscriptionState.collectLatest { resource ->
                if (resource is Resource.Success) {
                    val status = resource.data
                    if (status.isActive && status.packageInfo != null) {
                        binding.cardActiveSubscription.visible()
                        binding.cardNoSubscription.gone()
                        binding.tvSubPackageName.text = status.packageInfo.name
                        binding.tvSubExpiry.text = "Expires: ${status.expiryDate?.toDisplayDate() ?: "N/A"}"
                        binding.tvSubSignalsUsed.text = "Signals used today: ${status.signalsUsedToday}/${status.dailyLimit}"
                    } else {
                        binding.cardActiveSubscription.gone()
                        binding.cardNoSubscription.visible()
                    }
                }
            }
        }

        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.logoutState.collectLatest { loggedOut ->
                if (loggedOut) {
                    val intent = Intent(requireContext(), AuthActivity::class.java).apply {
                        flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
                    }
                    startActivity(intent)
                }
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
