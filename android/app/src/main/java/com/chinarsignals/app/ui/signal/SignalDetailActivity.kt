package com.chinarsignals.app.ui.signal

import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import com.chinarsignals.app.R
import com.chinarsignals.app.data.models.Signal
import com.chinarsignals.app.databinding.ActivitySignalDetailBinding
import com.chinarsignals.app.utils.Constants
import com.chinarsignals.app.utils.formatPrice
import com.chinarsignals.app.utils.formatRR
import com.chinarsignals.app.utils.toTimeAgo
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class SignalDetailActivity : AppCompatActivity() {

    private lateinit var binding: ActivitySignalDetailBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivitySignalDetailBinding.inflate(layoutInflater)
        setContentView(binding.root)

        setSupportActionBar(binding.toolbar)
        supportActionBar?.setDisplayHomeAsUpEnabled(true)
        supportActionBar?.title = ""

        val signal = intent.getParcelableExtra<Signal>(Constants.EXTRA_SIGNAL)
        if (signal != null) {
            bindSignal(signal)
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
        val (statusText, statusColor) = when (signal.status.lowercase()) {
            Constants.STATUS_WIN -> Pair("WIN", R.color.accent_green)
            Constants.STATUS_LOSS -> Pair("LOSS", R.color.accent_red)
            Constants.STATUS_ACTIVE -> Pair("ACTIVE", R.color.accent_blue)
            else -> Pair("PENDING", R.color.neutral_gray)
        }
        binding.tvStatus.text = statusText
        binding.tvStatus.setTextColor(ContextCompat.getColor(this, statusColor))

        // Time
        binding.tvCreatedAt.text = signal.createdAt.toTimeAgo()

        // Reasons
        binding.tvMarketStructure.text = signal.reason.marketStructure
        binding.tvMtfTrend.text = signal.reason.mtfTrend
        binding.tvSummary.text = signal.reason.summary

        if (!signal.reason.orderBlock.isNullOrBlank()) {
            binding.tvOrderBlockLabel.visibility = android.view.View.VISIBLE
            binding.tvOrderBlock.visibility = android.view.View.VISIBLE
            binding.tvOrderBlock.text = signal.reason.orderBlock
        }

        if (!signal.reason.fvg.isNullOrBlank()) {
            binding.tvFvgLabel.visibility = android.view.View.VISIBLE
            binding.tvFvg.visibility = android.view.View.VISIBLE
            binding.tvFvg.text = signal.reason.fvg
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

    override fun onSupportNavigateUp(): Boolean {
        onBackPressedDispatcher.onBackPressed()
        return true
    }
}
