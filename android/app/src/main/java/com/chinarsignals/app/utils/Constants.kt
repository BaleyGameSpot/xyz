package com.chinarsignals.app.utils

object Constants {
    val TIMEFRAMES = listOf("1M", "3M", "5M", "15M", "30M", "1H", "4H", "1D")

    val BASIC_TIMEFRAMES = listOf("15M", "30M", "1H")
    val BEST_TIMEFRAMES = listOf("5M", "15M", "30M", "1H", "4H")
    val PREMIUM_TIMEFRAMES = listOf("1M", "3M", "5M", "15M", "30M", "1H", "4H", "1D")

    const val EXTRA_SIGNAL = "extra_signal"
    const val EXTRA_SIGNAL_ID = "extra_signal_id"
    const val EXTRA_PACKAGE = "extra_package"
    const val EXTRA_WALLET_INFO = "extra_wallet_info"

    const val SIGNAL_TYPE_BUY = "BUY"
    const val SIGNAL_TYPE_SELL = "SELL"

    const val STATUS_PENDING = "pending"
    const val STATUS_ACTIVE = "active"
    const val STATUS_WIN = "win"
    const val STATUS_LOSS = "loss"

    const val SUB_NONE = "none"
    const val SUB_BASIC = "basic"
    const val SUB_BEST = "best"
    const val SUB_PREMIUM = "premium"
}
