package com.chinarsignals.app.ui.main.signals

import android.content.Intent
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.chinarsignals.app.databinding.FragmentSignalsBinding
import com.chinarsignals.app.ui.signal.SignalDetailActivity
import com.chinarsignals.app.utils.Constants
import com.chinarsignals.app.utils.Resource
import com.chinarsignals.app.utils.gone
import com.chinarsignals.app.utils.snackbarError
import com.chinarsignals.app.utils.visible
import com.google.android.material.chip.Chip
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch

@AndroidEntryPoint
class SignalsFragment : Fragment() {

    private var _binding: FragmentSignalsBinding? = null
    private val binding get() = _binding!!

    private val viewModel: SignalsViewModel by viewModels()
    private lateinit var signalAdapter: SignalAdapter

    override fun onCreateView(inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?): View {
        _binding = FragmentSignalsBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        setupRecyclerView()
        setupFilters()
        setupSwipeRefresh()
        observeData()
    }

    private fun setupRecyclerView() {
        signalAdapter = SignalAdapter { signal ->
            val intent = Intent(requireContext(), SignalDetailActivity::class.java).apply {
                putExtra(Constants.EXTRA_SIGNAL, signal)
            }
            startActivity(intent)
        }
        binding.rvSignals.apply {
            layoutManager = LinearLayoutManager(requireContext())
            adapter = signalAdapter
        }
    }

    private fun setupFilters() {
        // Type filter chips
        val types = listOf("ALL", "BUY", "SELL")
        types.forEach { type ->
            val chip = Chip(requireContext()).apply {
                text = type
                isCheckable = true
                isChecked = type == "ALL"
            }
            chip.setOnClickListener {
                viewModel.setFilterType(if (type == "ALL") null else type)
            }
            binding.chipGroupType.addView(chip)
        }

        // Timeframe chips
        val timeframes = listOf("ALL") + Constants.TIMEFRAMES
        timeframes.forEach { tf ->
            val chip = Chip(requireContext()).apply {
                text = tf
                isCheckable = true
                isChecked = tf == "ALL"
            }
            chip.setOnClickListener {
                viewModel.setFilterTimeframe(if (tf == "ALL") null else tf)
            }
            binding.chipGroupTimeframe.addView(chip)
        }
    }

    private fun setupSwipeRefresh() {
        binding.swipeRefresh.setColorSchemeResources(com.chinarsignals.app.R.color.accent_green)
        binding.swipeRefresh.setOnRefreshListener {
            viewModel.loadSignals()
        }
    }

    private fun observeData() {
        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.signalsState.collectLatest { resource ->
                binding.swipeRefresh.isRefreshing = false
                when (resource) {
                    is Resource.Loading -> {
                        binding.progressBar.visible()
                        binding.tvEmpty.gone()
                    }
                    is Resource.Success -> {
                        binding.progressBar.gone()
                        if (resource.data.isEmpty()) {
                            binding.tvEmpty.visible()
                            binding.rvSignals.gone()
                        } else {
                            binding.tvEmpty.gone()
                            binding.rvSignals.visible()
                            signalAdapter.submitList(resource.data)
                        }
                    }
                    is Resource.Error -> {
                        binding.progressBar.gone()
                        binding.root.snackbarError(resource.message)
                    }
                    null -> binding.progressBar.gone()
                }
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
