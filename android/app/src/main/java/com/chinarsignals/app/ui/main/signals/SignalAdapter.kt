package com.chinarsignals.app.ui.main.signals

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.core.content.ContextCompat
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.chinarsignals.app.R
import com.chinarsignals.app.data.models.Signal
import com.chinarsignals.app.databinding.ItemSignalBinding
import com.chinarsignals.app.utils.toTimeAgo

class SignalAdapter(
    private val onSignalClick: (Signal) -> Unit
) : ListAdapter<Signal, SignalAdapter.SignalViewHolder>(DiffCallback()) {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): SignalViewHolder {
        val binding = ItemSignalBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return SignalViewHolder(binding)
    }

    override fun onBindViewHolder(holder: SignalViewHolder, position: Int) {
        holder.bind(getItem(position))
    }

    inner class SignalViewHolder(private val binding: ItemSignalBinding) :
        RecyclerView.ViewHolder(binding.root) {

        fun bind(signal: Signal) {
            val context = binding.root.context

            binding.tvPair.text = signal.pair
            binding.tvTimeframe.text = signal.timeframe
            binding.tvTime.text = signal.createdAt.toTimeAgo()
            binding.tvConfidence.text = "${signal.confidenceScore}%"
            binding.progressConfidence.progress = signal.confidenceScore

            // Signal type badge
            if (signal.isBuy()) {
                binding.tvSignalType.text = "BUY"
                binding.tvSignalType.background = ContextCompat.getDrawable(context, R.drawable.badge_buy)
                binding.progressConfidence.progressTintList =
                    ContextCompat.getColorStateList(context, R.color.buy_green)
            } else {
                binding.tvSignalType.text = "SELL"
                binding.tvSignalType.background = ContextCompat.getDrawable(context, R.drawable.badge_sell)
                binding.progressConfidence.progressTintList =
                    ContextCompat.getColorStateList(context, R.color.sell_red)
            }

            // Status
            val (statusText, statusColor) = when (signal.status.lowercase()) {
                "win" -> Pair("WIN", R.color.accent_green)
                "loss" -> Pair("LOSS", R.color.accent_red)
                "active" -> Pair("ACTIVE", R.color.accent_blue)
                else -> Pair("PENDING", R.color.neutral_gray)
            }
            binding.tvStatus.text = statusText
            binding.tvStatus.setTextColor(ContextCompat.getColor(context, statusColor))

            binding.root.setOnClickListener { onSignalClick(signal) }
        }
    }

    class DiffCallback : DiffUtil.ItemCallback<Signal>() {
        override fun areItemsTheSame(oldItem: Signal, newItem: Signal) = oldItem.id == newItem.id
        override fun areContentsTheSame(oldItem: Signal, newItem: Signal) = oldItem == newItem
    }
}
