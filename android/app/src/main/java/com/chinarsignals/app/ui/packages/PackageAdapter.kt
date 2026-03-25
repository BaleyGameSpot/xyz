package com.chinarsignals.app.ui.packages

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.core.content.ContextCompat
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.chinarsignals.app.R
import com.chinarsignals.app.data.models.Package
import com.chinarsignals.app.databinding.ItemPackageBinding

class PackageAdapter(
    private val onPackageSelected: (Package) -> Unit
) : ListAdapter<Package, PackageAdapter.PackageViewHolder>(DiffCallback()) {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): PackageViewHolder {
        val binding = ItemPackageBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return PackageViewHolder(binding)
    }

    override fun onBindViewHolder(holder: PackageViewHolder, position: Int) {
        holder.bind(getItem(position))
    }

    inner class PackageViewHolder(private val binding: ItemPackageBinding) :
        RecyclerView.ViewHolder(binding.root) {

        fun bind(pkg: Package) {
            val context = binding.root.context

            binding.tvPackageName.text = pkg.name
            binding.tvPackageDescription.text = pkg.description

            if (pkg.hasDiscount() && pkg.promoPrice != null) {
                binding.tvPrice.text = "$${String.format("%.2f", pkg.promoPrice)}/mo"
                binding.tvOriginalPrice.visibility = View.VISIBLE
                binding.tvOriginalPrice.text = "$${String.format("%.2f", pkg.price)}/mo"
                binding.tvDiscount.visibility = View.VISIBLE
                binding.tvDiscount.text = "-${pkg.getDiscountPercentage()}%"
            } else {
                binding.tvPrice.text = "$${String.format("%.2f", pkg.price)}/mo"
                binding.tvOriginalPrice.visibility = View.GONE
                binding.tvDiscount.visibility = View.GONE
            }

            // Features
            val featuresText = buildString {
                pkg.features.take(5).forEach { feature ->
                    append("✓ $feature\n")
                }
                if (pkg.pairsLimit != null) append("✓ ${pkg.pairsLimit} Trading Pairs\n")
                else append("✓ Unlimited Trading Pairs\n")
                append("✓ ${pkg.dailySignalsLimit} Signals/Day\n")
                append("✓ Timeframes: ${pkg.timeframes.joinToString(", ")}")
            }
            binding.tvFeatures.text = featuresText.trim()

            // Best package highlight
            if (pkg.isBest) {
                binding.cardPackage.setCardBackgroundColor(ContextCompat.getColor(context, R.color.accent_green))
                binding.tvBestBadge.visibility = View.VISIBLE
                binding.tvPackageName.setTextColor(ContextCompat.getColor(context, R.color.bg_primary))
                binding.tvPrice.setTextColor(ContextCompat.getColor(context, R.color.bg_primary))
                binding.tvPackageDescription.setTextColor(ContextCompat.getColor(context, R.color.bg_secondary))
                binding.tvFeatures.setTextColor(ContextCompat.getColor(context, R.color.bg_secondary))
                binding.btnSelectPackage.setBackgroundColor(ContextCompat.getColor(context, R.color.bg_primary))
                binding.btnSelectPackage.setTextColor(ContextCompat.getColor(context, R.color.accent_green))
            } else {
                binding.cardPackage.setCardBackgroundColor(ContextCompat.getColor(context, R.color.bg_card))
                binding.tvBestBadge.visibility = View.GONE
                binding.tvPackageName.setTextColor(ContextCompat.getColor(context, R.color.text_primary))
                binding.tvPrice.setTextColor(ContextCompat.getColor(context, R.color.accent_green))
                binding.tvPackageDescription.setTextColor(ContextCompat.getColor(context, R.color.text_secondary))
                binding.tvFeatures.setTextColor(ContextCompat.getColor(context, R.color.text_secondary))
            }

            binding.btnSelectPackage.setOnClickListener { onPackageSelected(pkg) }
            binding.root.setOnClickListener { onPackageSelected(pkg) }
        }
    }

    class DiffCallback : DiffUtil.ItemCallback<Package>() {
        override fun areItemsTheSame(oldItem: Package, newItem: Package) = oldItem.id == newItem.id
        override fun areContentsTheSame(oldItem: Package, newItem: Package) = oldItem == newItem
    }
}
