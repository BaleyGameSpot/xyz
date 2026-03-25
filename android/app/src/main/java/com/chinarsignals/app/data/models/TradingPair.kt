package com.chinarsignals.app.data.models

import android.os.Parcelable
import com.google.gson.annotations.SerializedName
import kotlinx.parcelize.Parcelize

@Parcelize
data class TradingPair(
    @SerializedName("id") val id: Int,
    @SerializedName("symbol") val symbol: String,
    @SerializedName("name") val name: String,
    @SerializedName("type") val type: String,
    @SerializedName("is_active") val isActive: Boolean,
    @SerializedName("base_currency") val baseCurrency: String? = null,
    @SerializedName("quote_currency") val quoteCurrency: String? = null
) : Parcelable {
    fun isForex(): Boolean = type.equals("forex", ignoreCase = true)
    fun isCrypto(): Boolean = type.equals("crypto", ignoreCase = true)
}
