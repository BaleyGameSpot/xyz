package com.chinarsignals.app.data.models

import android.os.Parcelable
import com.google.gson.annotations.SerializedName
import kotlinx.parcelize.Parcelize

@Parcelize
data class User(
    @SerializedName("id") val id: Int,
    @SerializedName("name") val name: String,
    @SerializedName("email") val email: String,
    @SerializedName("avatar") val avatar: String?,
    @SerializedName("subscription_type") val subscriptionType: String = "none",
    @SerializedName("subscription_expiry") val subscriptionExpiry: String?,
    @SerializedName("fcm_token") val fcmToken: String? = null,
    @SerializedName("role") val role: String? = null
) : Parcelable {
    fun isSubscribed(): Boolean = subscriptionType != "none" && subscriptionType.isNotBlank()
    fun getDisplayName(): String = name.split(" ").firstOrNull() ?: name
    fun isAdmin(): Boolean = role == "admin"
}
