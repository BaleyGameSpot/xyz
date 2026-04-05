package com.chinarsignals.app.ui.signal

import android.os.Bundle
import android.view.View
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import androidx.lifecycle.lifecycleScope
import com.chinarsignals.app.R
import com.chinarsignals.app.data.local.PreferenceManager
import com.chinarsignals.app.data.models.Signal
import com.chinarsignals.app.data.repository.SignalRepository
import com.chinarsignals.app.databinding.ActivitySignalDetailBinding
import com.chinarsignals.app.utils.Constants
import com.chinarsignals.app.utils.Resource
import com.chinarsignals.app.utils.formatPrice
import com.chinarsignals.app.utils.formatRR
import com.chinarsignals.app.utils.toTimeAgo
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.flow.launchIn
import kotlinx.coroutines.flow.onEach
import javax.inject.Inject

@AndroidEntryPoint
class SignalDetailActivity : AppCompatActivity() {

    private lateinit var binding: ActivitySignalDetailBinding

    @Inject lateinit var prefManager: PreferenceManager
    @Inject lateinit var signalRepository: SignalRepository

    private var currentSignalId: Int = -1

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivitySignalDetailBinding.inflate(layoutInflater)
        setContentView(binding.root)

        setSupportActionBar(binding.toolbar)
        supportActionBar?.setDisplayHomeAsUpEnabled(true)
        supportActionBar?.title = ""

        val signal = intent.getParcelableExtra<Signal>(Constants.EXTRA_SIGNAL)
        if (signal != null) {
            currentSignalId = signal.id
            bindSignal(signal)
            setupMarkResultButtons()
        } else {
            finish()
        }
    }

    private fun bindSignal(signal: Signal) {
        // Header
        binding.tvPairTimeframe.text = "${signal.pair} / ${signal.timeframe}"
        binding.tvSignalType.text = signal.signalType

        if (signal.isBuy()) {
            binding.tvSignalType.background = ContextCompat.getDrawable(this, R.drawable.badge_buy_large)
            binding.tvSignalType.setTextColor(ContextCompat.getColor(this, R.color.bg_primary))
        } else {
            binding.tvSignalType.background = ContextCompat.getDrawable(this, R.drawable.badge_sell_large)
            binding.tvSignalType.setTextColor(ContextCompat.getColor(this, R.color.bg_primary))
        }

        // Signal method (BOS/CHoCH or OB+FVG Retest)
        binding.tvSignalMethod.text = signal.signalMethod ?: "BOS/CHoCH"

        // Confidence meter
        val confidence = signal.confidenceScore
        binding.circularProgressConfidence.progress = confidence
        binding.tvConfidenceValue.text = "${confidence}%"
        binding.tvConfidenceLabel.text = signal.getConfidenceLevel()
        val confidenceColor = when {
            confidence >= 80 -> ContextCompat.getColor(this, R.color.accent_green)
            confidence >= 60 -> ContextCompat.getColor(this, R.color.accent_gold)
            else -> ContextCompat.getColor(this, R.color.accent_red)
        }
        binding.tvConfidenceLabel.setTextColor(confidenceColor)

        // Price levels
        binding.tvEntryPrice.text = signal.entryPrice.formatPrice()
        binding.tvStopLoss.text = signal.stopLoss.formatPrice()
        binding.tvTakeProfit.text = signal.takeProfit.formatPrice()

        // Risk/Reward
        val rr = signal.riskReward ?: signal.calculateRiskReward()
        binding.tvRiskReward.text = rr.formatRR()

        // Status badge
        updateStatusBadge(signal.status)

        // Time
        binding.tvCreatedAt.text = signal.createdAt.toTimeAgo()

        // Reasons
        val reason = signal.reason
        binding.tvMarketStructure.text = reason?.marketStructure ?: "—"
        binding.tvMtfTrend.text = reason?.mtfTrend ?: "—"
        binding.tvSummary.text = reason?.summary ?: "—"

        if (!reason?.orderBlock.isNullOrBlank()) {
            binding.tvOrderBlockLabel.visibility = View.VISIBLE
            binding.tvOrderBlock.visibility = View.VISIBLE
            binding.tvOrderBlock.text = reason?.orderBlock
        }

        if (!reason?.fvg.isNullOrBlank()) {
            binding.tvFvgLabel.visibility = View.VISIBLE
            binding.tvFvg.visibility = View.VISIBLE
            binding.tvFvg.text = reason?.fvg
        }

        // SL color based on signal type
        if (signal.isBuy()) {
            binding.tvStopLoss.setTextColor(ContextCompat.getColor(this, R.color.accent_red))
            binding.tvTakeProfit.setTextColor(ContextCompat.getColor(this, R.color.accent_green))
        } else {
            binding.tvStopLoss.setTextColor(ContextCompat.getColor(this, R.color.accent_green))
            binding.tvTakeProfit.setTextColor(ContextCompat.getColor(this, R.color.accent_red))
        }
    }

    private fun updateStatusBadge(status: String) {
        val (statusText, statusColor) = when (status.lowercase()) {
            Constants.STATUS_WIN -> Pair("WIN", R.color.accent_green)
            Constants.STATUS_LOSS -> Pair("LOSS", R.color.accent_red)
            Constants.STATUS_ACTIVE -> Pair("ACTIVE", R.color.accent_blue)
            else -> Pair("PENDING", R.color.neutral_gray)
        }
        binding.tvStatus.text = statusText
        binding.tvStatus.setTextColor(ContextCompat.getColor(this, statusColor))
    }

    private fun setupMarkResultButtons() {
        val isAdmin = prefManager.getUser()?.isAdmin() == true
        if (!isAdmin) return

        binding.cardMarkResult.visibility = View.VISIBLE

        binding.btnMarkWin.setOnClickListener {
            confirmMark("WIN", "win")
        }
        binding.btnMarkLoss.setOnClickListener {
            confirmMark("LOSS", "loss")
        }
        binding.btnMarkPending.setOnClickListener {
            confirmMark("RESET to Pending", "pending")
        }
    }

    private fun confirmMark(label: String, status: String) {
        AlertDialog.Builder(this)
            .setTitle("Mark Signal")
            .setMessage("Mark this signal as $label?")
            .setPositiveButton("Yes") { _, _ -> doMark(status) }
            .setNegativeButton("Cancel", null)
            .show()
    }

    private fun doMark(status: String) {
        if (currentSignalId == -1) return
        signalRepository.markSignalStatus(currentSignalId, status)
            .onEach { result ->
                when (result) {
                    is Resource.Loading -> {
                        binding.btnMarkWin.isEnabled = false
                        binding.btnMarkLoss.isEnabled = false
                        binding.btnMarkPending.isEnabled = false
                    }
                    is Resource.Success -> {
                        binding.btnMarkWin.isEnabled = true
                        binding.btnMarkLoss.isEnabled = true
                        binding.btnMarkPending.isEnabled = true
                        updateStatusBadge(status)
                        binding.tvMarkResultMsg.visibility = View.VISIBLE
                        binding.tvMarkResultMsg.text = "Status updated to ${status.uppercase()}"
                        val msgColor = when (status) {
                            "win" -> R.color.accent_green
                            "loss" -> R.color.accent_red
                            else -> R.color.neutral_gray
                        }
                        binding.tvMarkResultMsg.setTextColor(ContextCompat.getColor(this, msgColor))
                    }
                    is Resource.Error -> {
                        binding.btnMarkWin.isEnabled = true
                        binding.btnMarkLoss.isEnabled = true
                        binding.btnMarkPending.isEnabled = true
                        Toast.makeText(this, result.message ?: "Failed", Toast.LENGTH_SHORT).show()
                    }
                }
            }.launchIn(lifecycleScope)
    }

    override fun onSupportNavigateUp(): Boolean {
        onBackPressedDispatcher.onBackPressed()
        return true
    }
}
