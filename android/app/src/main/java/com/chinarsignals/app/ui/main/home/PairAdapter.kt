package com.chinarsignals.app.ui.main.home

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.core.content.ContextCompat
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.chinarsignals.app.R
import com.chinarsignals.app.data.models.TradingPair
import com.chinarsignals.app.databinding.ItemPairBinding

class PairAdapter(
    private val onPairSelected: (TradingPair) -> Unit
) : ListAdapter<TradingPair, PairAdapter.PairViewHolder>(DiffCallback()) {

    private var selectedPairId: Int = -1

    fun setSelectedPair(pairId: Int) {
        val oldSelected = currentList.indexOfFirst { it.id == selectedPairId }
        val newSelected = currentList.indexOfFirst { it.id == pairId }
        selectedPairId = pairId
        if (oldSelected >= 0) notifyItemChanged(oldSelected)
        if (newSelected >= 0) notifyItemChanged(newSelected)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): PairViewHolder {
        val binding = ItemPairBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return PairViewHolder(binding)
    }

    override fun onBindViewHolder(holder: PairViewHolder, position: Int) {
        holder.bind(getItem(position), getItem(position).id == selectedPairId)
    }

    inner class PairViewHolder(private val binding: ItemPairBinding) :
        RecyclerView.ViewHolder(binding.root) {

        fun bind(pair: TradingPair, isSelected: Boolean) {
            binding.tvPairSymbol.text = pair.symbol
            binding.tvPairType.text = pair.type.uppercase()

            val context = binding.root.context
            if (isSelected) {
                binding.root.setCardBackgroundColor(ContextCompat.getColor(context, R.color.accent_green))
                binding.tvPairSymbol.setTextColor(ContextCompat.getColor(context, R.color.bg_primary))
                binding.tvPairType.setTextColor(ContextCompat.getColor(context, R.color.bg_secondary))
            } else {
                binding.root.setCardBackgroundColor(ContextCompat.getColor(context, R.color.bg_card))
                binding.tvPairSymbol.setTextColor(ContextCompat.getColor(context, R.color.text_primary))
                binding.tvPairType.setTextColor(ContextCompat.getColor(context, R.color.text_secondary))
            }

            binding.root.setOnClickListener {
                onPairSelected(pair)
            }
        }
    }

    class DiffCallback : DiffUtil.ItemCallback<TradingPair>() {
        override fun areItemsTheSame(oldItem: TradingPair, newItem: TradingPair) = oldItem.id == newItem.id
        override fun areContentsTheSame(oldItem: TradingPair, newItem: TradingPair) = oldItem == newItem
    }
}
