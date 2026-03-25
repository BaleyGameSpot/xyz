package com.chinarsignals.app.data.models

import com.google.gson.annotations.SerializedName

data class ApiResponse<T>(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String?,
    @SerializedName("data") val data: T?,
    @SerializedName("errors") val errors: Map<String, List<String>>? = null
)

data class AuthResponse(
    @SerializedName("token") val token: String,
    @SerializedName("user") val user: User,
    @SerializedName("token_type") val tokenType: String = "Bearer"
)

data class LoginRequest(
    @SerializedName("email") val email: String,
    @SerializedName("password") val password: String
)

data class RegisterRequest(
    @SerializedName("name") val name: String,
    @SerializedName("email") val email: String,
    @SerializedName("password") val password: String,
    @SerializedName("password_confirmation") val passwordConfirmation: String
)

data class GoogleAuthRequest(
    @SerializedName("id_token") val idToken: String,
    @SerializedName("email") val email: String,
    @SerializedName("name") val name: String,
    @SerializedName("avatar") val avatar: String?
)

data class FcmTokenRequest(
    @SerializedName("fcm_token") val fcmToken: String
)

data class PaymentVerifyRequest(
    @SerializedName("package_id") val packageId: Int,
    @SerializedName("tx_hash") val txHash: String
)

data class WalletInfo(
    @SerializedName("address") val address: String,
    @SerializedName("currency") val currency: String,
    @SerializedName("network") val network: String,
    @SerializedName("amount") val amount: Double,
    @SerializedName("qr_code") val qrCode: String?
)
