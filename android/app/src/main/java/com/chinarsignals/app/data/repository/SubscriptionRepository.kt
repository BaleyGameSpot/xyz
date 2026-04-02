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
class SubscriptionRepository @Inject constructor(
    private val apiService: ApiService,
    private val preferenceManager: PreferenceManager
) {

    fun getPackages(): Flow<Resource<List<Package>>> = flow {
        emit(Resource.Loading())
        try {
            val response = apiService.getPackages()
            if (response.isSuccessful && response.body()?.success == true) {
                emit(Resource.Success(response.body()!!.data ?: emptyList()))
            } else {
                emit(Resource.Error(response.body()?.message ?: "Failed to load packages"))
            }
        } catch (e: Exception) {
            emit(Resource.Error(e.message ?: "Network error"))
        }
    }

    fun getSubscriptionStatus(): Flow<Resource<SubscriptionStatus>> = flow {
        emit(Resource.Loading())
        try {
            val response = apiService.getSubscriptionStatus()
            if (response.isSuccessful && response.body()?.success == true) {
                val status = response.body()!!.data!!
                if (status.isActive && status.packageInfo != null) {
                    preferenceManager.saveSubscriptionType(status.packageInfo.slug)
                }
                emit(Resource.Success(status))
            } else {
                emit(Resource.Error(response.body()?.message ?: "Failed to load subscription"))
            }
        } catch (e: Exception) {
            emit(Resource.Error(e.message ?: "Network error"))
        }
    }

    fun purchaseSubscription(packageId: Int): Flow<Resource<WalletInfo>> = flow {
        emit(Resource.Loading())
        try {
            val request = PurchaseRequest(packageId, "", "", "USDT")
            val response = apiService.purchaseSubscription(request)
            if (response.isSuccessful && response.body()?.success == true) {
                emit(Resource.Success(response.body()!!.data!!))
            } else {
                emit(Resource.Error(response.body()?.message ?: "Purchase initiation failed"))
            }
        } catch (e: Exception) {
            emit(Resource.Error(e.message ?: "Network error"))
        }
    }

    fun verifyPayment(packageId: Int, txHash: String): Flow<Resource<SubscriptionStatus>> = flow {
        emit(Resource.Loading())
        try {
            val request = PaymentVerifyRequest(packageId, txHash)
            val response = apiService.verifyPayment(request)
            if (response.isSuccessful && response.body()?.success == true) {
                val status = response.body()!!.data!!
                if (status.isActive && status.packageInfo != null) {
                    preferenceManager.saveSubscriptionType(status.packageInfo.slug)
                }
                emit(Resource.Success(status))
            } else {
                emit(Resource.Error(response.body()?.message ?: "Payment verification failed"))
            }
        } catch (e: Exception) {
            emit(Resource.Error(e.message ?: "Network error"))
        }
    }
}
