package com.chinarsignals.app.data.repository

import com.chinarsignals.app.data.api.ApiService
import com.chinarsignals.app.data.local.PreferenceManager
import com.chinarsignals.app.data.models.*
import com.chinarsignals.app.utils.Resource
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class UserRepository @Inject constructor(
    private val apiService: ApiService,
    private val preferenceManager: PreferenceManager
) {

    fun updateFcmToken(fcmToken: String): Flow<Resource<Unit>> = flow {
        emit(Resource.Loading())
        try {
            val response = apiService.updateFcmToken(FcmTokenRequest(fcmToken))
            if (response.isSuccessful && response.body()?.success == true) {
                preferenceManager.saveFcmToken(fcmToken)
                emit(Resource.Success(Unit))
            } else {
                emit(Resource.Error(response.body()?.message ?: "Failed to update FCM token"))
            }
        } catch (e: Exception) {
            emit(Resource.Error(e.message ?: "Network error"))
        }
    }

    fun getCachedUser(): User? = preferenceManager.getUser()

    fun isSubscribed(): Boolean = preferenceManager.isSubscribed()

    fun getSubscriptionType(): String = preferenceManager.getSubscriptionType()
}
