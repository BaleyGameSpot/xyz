package com.chinarsignals.app.data.models

import android.os.Parcelable
import com.google.gson.annotations.SerializedName
import kotlinx.parcelize.Parcelize

@Parcelize
data class Package(
    @SerializedName("id") val id: Int,
    @SerializedName("name") val name: String,
    @SerializedName("slug") val slug: String,
    @SerializedName("price") val price: Double,
    @SerializedName("promo_price") val promoPrice: Double?,
    @SerializedName("pairs_limit") val pairsLimit: Int?,
    @SerializedName("daily_signals_limit") val dailySignalsLimit: Int,
    @SerializedName("timeframes") val timeframes: List<String>,
    @SerializedName("description") val description: String,
    @SerializedName("features") val features: List<String> = emptyList(),
    @SerializedName("is_best") val isBest: Boolean = false,
    @SerializedName("duration_days") val durationDays: Int = 30
) : Parcelable {
    fun getEffectivePrice(): Double = promoPrice ?: price
    fun hasDiscount(): Boolean = promoPrice != null && promoPrice < price
    fun getDiscountPercentage(): Int {
        if (!hasDiscount()) return 0
        return ((price - promoPrice!!) / price * 100).toInt()
    }
    fun isPairsUnlimited(): Boolean = pairsLimit == null
}

data class PurchaseRequest(
    @SerializedName("package_id") val packageId: Int,
    @SerializedName("tx_hash") val txHash: String,
    @SerializedName("wallet_address") val walletAddress: String,
    @SerializedName("currency") val currency: String = "USDT"
)

data class SubscriptionStatus(
    @SerializedName("active") val isActive: Boolean,
    @SerializedName("package") val packageInfo: Package?,
    @SerializedName("expiry") val expiryDate: String?,
    @SerializedName("signals_used_today") val signalsUsedToday: Int,
    @SerializedName("daily_limit") val dailyLimit: Int
)
