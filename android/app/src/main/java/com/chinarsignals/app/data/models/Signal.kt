package com.chinarsignals.app.data.models

import android.os.Parcelable
import com.google.gson.annotations.SerializedName
import kotlinx.parcelize.Parcelize

@Parcelize
data class Signal(
    @SerializedName("id") val id: Int,
    @SerializedName("pair") val pair: String,
    @SerializedName("timeframe") val timeframe: String,
    @SerializedName("signal_type") val signalType: String,
    @SerializedName("entry_price") val entryPrice: Double,
    @SerializedName("stop_loss") val stopLoss: Double,
    @SerializedName("take_profit") val takeProfit: Double,
    @SerializedName("confidence_score") val confidenceScore: Int,
    @SerializedName("reason") val reason: SignalReason,
    @SerializedName("status") val status: String,
    @SerializedName("created_at") val createdAt: String,
    @SerializedName("risk_reward") val riskReward: Double? = null
) : Parcelable {
    fun isBuy(): Boolean = signalType.equals("BUY", ignoreCase = true)
    fun isSell(): Boolean = signalType.equals("SELL", ignoreCase = true)
    fun getConfidenceLevel(): String = when {
        confidenceScore >= 80 -> "HIGH"
        confidenceScore >= 60 -> "MEDIUM"
        else -> "LOW"
    }
    fun calculateRiskReward(): Double {
        val risk = Math.abs(entryPrice - stopLoss)
        val reward = Math.abs(takeProfit - entryPrice)
        return if (risk > 0) reward / risk else 0.0
    }
}

@Parcelize
data class SignalReason(
    @SerializedName("market_structure") val marketStructure: String,
    @SerializedName("order_block") val orderBlock: String?,
    @SerializedName("fvg") val fvg: String?,
    @SerializedName("mtf_trend") val mtfTrend: String,
    @SerializedName("summary") val summary: String
) : Parcelable

data class TodayStats(
    @SerializedName("remaining") val signalsAvailable: Int,
    @SerializedName("total") val signalsUsed: Int,
    @SerializedName("win_rate") val winRate: Double,
    @SerializedName("daily_limit") val dailyLimit: Int
)

data class AnalyzeRequest(
    @SerializedName("symbol") val symbol: String,
    @SerializedName("timeframe") val timeframe: String
)
