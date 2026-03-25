package com.chinarsignals.app.data.api

import com.chinarsignals.app.data.models.*
import retrofit2.Response
import retrofit2.http.*

interface ApiService {

    // ─── Auth ───────────────────────────────────────────────────────────────
    @POST("api/auth/login")
    suspend fun login(@Body request: LoginRequest): Response<ApiResponse<AuthResponse>>

    @POST("api/auth/register")
    suspend fun register(@Body request: RegisterRequest): Response<ApiResponse<AuthResponse>>

    @POST("api/auth/google")
    suspend fun googleAuth(@Body request: GoogleAuthRequest): Response<ApiResponse<AuthResponse>>

    @GET("api/auth/me")
    suspend fun getMe(): Response<ApiResponse<User>>

    // ─── Pairs ──────────────────────────────────────────────────────────────
    @GET("api/pairs")
    suspend fun getPairs(): Response<ApiResponse<List<TradingPair>>>

    // ─── Packages ───────────────────────────────────────────────────────────
    @GET("api/packages")
    suspend fun getPackages(): Response<ApiResponse<List<Package>>>

    // ─── Signals ────────────────────────────────────────────────────────────
    @GET("api/signals")
    suspend fun getSignals(
        @Query("pair") pair: String? = null,
        @Query("timeframe") timeframe: String? = null,
        @Query("signal_type") signalType: String? = null,
        @Query("page") page: Int = 1
    ): Response<ApiResponse<List<Signal>>>

    @POST("api/signals/analyze")
    suspend fun analyzeSignal(@Body request: AnalyzeRequest): Response<ApiResponse<Signal>>

    @GET("api/signals/{id}")
    suspend fun getSignalById(@Path("id") id: Int): Response<ApiResponse<Signal>>

    @GET("api/signals/today-stats")
    suspend fun getTodayStats(): Response<ApiResponse<TodayStats>>

    // ─── Subscription ───────────────────────────────────────────────────────
    @GET("api/subscription/status")
    suspend fun getSubscriptionStatus(): Response<ApiResponse<SubscriptionStatus>>

    @POST("api/subscription/purchase")
    suspend fun purchaseSubscription(
        @Body request: PurchaseRequest
    ): Response<ApiResponse<WalletInfo>>

    @POST("api/subscription/verify-payment")
    suspend fun verifyPayment(
        @Body request: PaymentVerifyRequest
    ): Response<ApiResponse<SubscriptionStatus>>

    // ─── User ───────────────────────────────────────────────────────────────
    @PUT("api/user/fcm-token")
    suspend fun updateFcmToken(@Body request: FcmTokenRequest): Response<ApiResponse<Unit>>
}
