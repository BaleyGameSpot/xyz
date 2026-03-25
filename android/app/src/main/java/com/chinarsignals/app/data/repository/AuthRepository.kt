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
class AuthRepository @Inject constructor(
    private val apiService: ApiService,
    private val preferenceManager: PreferenceManager
) {

    fun login(email: String, password: String): Flow<Resource<AuthResponse>> = flow {
        emit(Resource.Loading())
        try {
            val response = apiService.login(LoginRequest(email, password))
            if (response.isSuccessful && response.body()?.success == true) {
                val authData = response.body()!!.data!!
                preferenceManager.saveAuthToken(authData.token)
                preferenceManager.saveUser(authData.user)
                preferenceManager.saveSubscriptionType(authData.user.subscriptionType)
                emit(Resource.Success(authData))
            } else {
                val errorMsg = response.body()?.message ?: "Login failed. Please check your credentials."
                emit(Resource.Error(errorMsg))
            }
        } catch (e: Exception) {
            emit(Resource.Error(e.message ?: "Network error. Please try again."))
        }
    }

    fun register(name: String, email: String, password: String, confirmPassword: String): Flow<Resource<AuthResponse>> = flow {
        emit(Resource.Loading())
        try {
            val request = RegisterRequest(name, email, password, confirmPassword)
            val response = apiService.register(request)
            if (response.isSuccessful && response.body()?.success == true) {
                val authData = response.body()!!.data!!
                preferenceManager.saveAuthToken(authData.token)
                preferenceManager.saveUser(authData.user)
                preferenceManager.saveSubscriptionType(authData.user.subscriptionType)
                emit(Resource.Success(authData))
            } else {
                val errorMsg = response.body()?.message ?: "Registration failed. Please try again."
                emit(Resource.Error(errorMsg))
            }
        } catch (e: Exception) {
            emit(Resource.Error(e.message ?: "Network error. Please try again."))
        }
    }

    fun googleAuth(idToken: String, email: String, name: String, avatar: String?): Flow<Resource<AuthResponse>> = flow {
        emit(Resource.Loading())
        try {
            val request = GoogleAuthRequest(idToken, email, name, avatar)
            val response = apiService.googleAuth(request)
            if (response.isSuccessful && response.body()?.success == true) {
                val authData = response.body()!!.data!!
                preferenceManager.saveAuthToken(authData.token)
                preferenceManager.saveUser(authData.user)
                preferenceManager.saveSubscriptionType(authData.user.subscriptionType)
                emit(Resource.Success(authData))
            } else {
                val errorMsg = response.body()?.message ?: "Google sign-in failed."
                emit(Resource.Error(errorMsg))
            }
        } catch (e: Exception) {
            emit(Resource.Error(e.message ?: "Network error. Please try again."))
        }
    }

    fun getMe(): Flow<Resource<User>> = flow {
        emit(Resource.Loading())
        try {
            val response = apiService.getMe()
            if (response.isSuccessful && response.body()?.success == true) {
                val user = response.body()!!.data!!
                preferenceManager.saveUser(user)
                preferenceManager.saveSubscriptionType(user.subscriptionType)
                emit(Resource.Success(user))
            } else {
                emit(Resource.Error("Failed to fetch user info"))
            }
        } catch (e: Exception) {
            emit(Resource.Error(e.message ?: "Network error"))
        }
    }

    fun logout() {
        preferenceManager.clearAuth()
    }

    fun isLoggedIn(): Boolean = preferenceManager.isLoggedIn()

    fun getCachedUser(): User? = preferenceManager.getUser()
}
