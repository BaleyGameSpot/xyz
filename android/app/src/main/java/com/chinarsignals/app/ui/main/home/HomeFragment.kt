package com.chinarsignals.app.ui.main.home

import android.content.Intent
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.view.animation.AnimationUtils
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.GridLayoutManager
import com.chinarsignals.app.R
import com.chinarsignals.app.databinding.FragmentHomeBinding
import com.chinarsignals.app.ui.packages.PackagesActivity
import com.chinarsignals.app.ui.signal.SignalDetailActivity
import com.chinarsignals.app.utils.Constants
import com.chinarsignals.app.utils.Resource
import com.chinarsignals.app.utils.getCurrentDateFormatted
import com.chinarsignals.app.utils.getGreeting
import com.chinarsignals.app.utils.gone
import com.chinarsignals.app.utils.snackbarError
import com.chinarsignals.app.utils.toast
import com.chinarsignals.app.utils.visible
import com.google.android.material.chip.Chip
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch

@AndroidEntryPoint
class HomeFragment : Fragment() {

    private var _binding: FragmentHomeBinding? = null
    private val binding get() = _binding!!

    private val viewModel: HomeViewModel by viewModels()
    private lateinit var pairAdapter: PairAdapter

    override fun onCreateView(inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?): View {
        _binding = FragmentHomeBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        setupHeader()
        setupPairRecyclerView()
        setupTimeframeChips()
        setupAnalyzeButton()
        observeData()
    }

    private fun setupHeader() {
        val user = viewModel.getCachedUser()
        val greeting = getGreeting()
        binding.tvGreeting.text = if (user != null) "$greeting, ${user.getDisplayName()}!" else "$greeting!"
        binding.tvDate.text = getCurrentDateFormatted()
    }

    private fun setupPairRecyclerView() {
        pairAdapter = PairAdapter { pair ->
            viewModel.selectPair(pair.id)
            pairAdapter.setSelectedPair(pair.id)
        }
        binding.rvPairs.apply {
            layoutManager = GridLayoutManager(requireContext(), 3)
            adapter = pairAdapter
        }
    }

    private fun setupTimeframeChips() {
        val timeframes = Constants.TIMEFRAMES
        binding.chipGroupTimeframes.removeAllViews()
        timeframes.forEach { tf ->
            val chip = Chip(requireContext()).apply {
                text = tf
                isCheckable = true
                setChipBackgroundColorResource(R.color.chip_background_selector)
                setTextColor(resources.getColorStateList(R.color.chip_text_selector, null))
                chipStrokeWidth = 1f
                setChipStrokeColorResource(R.color.bg_elevated)
            }
            chip.setOnClickListener {
                viewModel.selectTimeframe(tf)
                updateChipSelection(tf)
            }
            binding.chipGroupTimeframes.addView(chip)
        }
        updateChipSelection(viewModel.selectedTimeframe.value)
    }

    private fun updateChipSelection(selectedTf: String) {
        for (i in 0 until binding.chipGroupTimeframes.childCount) {
            val chip = binding.chipGroupTimeframes.getChildAt(i) as? Chip
            chip?.isChecked = chip?.text == selectedTf
        }
    }

    private fun setupAnalyzeButton() {
        val pulse = AnimationUtils.loadAnimation(requireContext(), R.anim.pulse)
        binding.btnAnalyzeTrade.startAnimation(pulse)

        binding.btnAnalyzeTrade.setOnClickListener {
            if (!viewModel.isSubscribed()) {
                startActivity(Intent(requireContext(), PackagesActivity::class.java))
                return@setOnClickListener
            }
            if (viewModel.selectedPairId.value == -1) {
                toast("Please select a trading pair first")
                return@setOnClickListener
            }
            viewModel.analyzeSignal()
        }
    }

    private fun observeData() {
        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.pairsState.collectLatest { resource ->
                when (resource) {
                    is Resource.Loading -> binding.pairsProgressBar.visible()
                    is Resource.Success -> {
                        binding.pairsProgressBar.gone()
                        val activePairs = resource.data.filter { it.isActive }
                        pairAdapter.submitList(activePairs)
                        val savedId = viewModel.selectedPairId.value
                        if (savedId != -1) pairAdapter.setSelectedPair(savedId)
                    }
                    is Resource.Error -> {
                        binding.pairsProgressBar.gone()
                        binding.root.snackbarError(resource.message)
                    }
                    null -> {}
                }
            }
        }

        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.statsState.collectLatest { resource ->
                if (resource is Resource.Success) {
                    val stats = resource.data
                    binding.tvSignalsAvailable.text = "${stats.signalsAvailable}"
                    binding.tvSignalsUsed.text = "${stats.signalsUsed}/${stats.dailyLimit}"
                    binding.tvWinRate.text = String.format("%.0f%%", stats.winRate)
                    val progress = if (stats.dailyLimit > 0)
                        (stats.signalsUsed.toFloat() / stats.dailyLimit * 100).toInt()
                    else 0
                    binding.progressSignals.progress = progress
                }
            }
        }

        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.subscriptionState.collectLatest { resource ->
                if (resource is Resource.Success) {
                    val status = resource.data
                    if (status.isActive && status.packageInfo != null) {
                        binding.tvSubscriptionName.text = status.packageInfo.name
                        binding.tvSubscriptionExpiry.text = "Expires: ${status.expiryDate ?: "N/A"}"
                        binding.cardSubscription.visible()
                        binding.cardNoSubscription.gone()
                    } else {
                        binding.cardSubscription.gone()
                        binding.cardNoSubscription.visible()
                    }
                }
            }
        }

        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.analyzeState.collectLatest { resource ->
                when (resource) {
                    is Resource.Loading -> {
                        binding.btnAnalyzeTrade.isEnabled = false
                        binding.analyzeProgress.visible()
                        binding.btnAnalyzeTrade.text = "ANALYZING..."
                    }
                    is Resource.Success -> {
                        binding.btnAnalyzeTrade.isEnabled = true
                        binding.analyzeProgress.gone()
                        binding.btnAnalyzeTrade.text = "ANALYZE TRADE"
                        val signal = resource.data
                        val intent = Intent(requireContext(), SignalDetailActivity::class.java).apply {
                            putExtra(Constants.EXTRA_SIGNAL, signal)
                        }
                        startActivity(intent)
                        viewModel.resetAnalyzeState()
                    }
                    is Resource.Error -> {
                        binding.btnAnalyzeTrade.isEnabled = true
                        binding.analyzeProgress.gone()
                        binding.btnAnalyzeTrade.text = "ANALYZE TRADE"
                        binding.root.snackbarError(resource.message)
                        viewModel.resetAnalyzeState()
                    }
                    null -> {
                        binding.btnAnalyzeTrade.isEnabled = true
                        binding.analyzeProgress.gone()
                        binding.btnAnalyzeTrade.text = "ANALYZE TRADE"
                    }
                }
            }
        }

        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.selectedTimeframe.collectLatest { tf ->
                updateChipSelection(tf)
            }
        }
    }

    override fun onResume() {
        super.onResume()
        // Refresh subscription status and stats each time screen is visible
        // (e.g. after returning from PackagesActivity or after admin assigns subscription)
        viewModel.loadSubscriptionStatus()
        viewModel.loadTodayStats()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
