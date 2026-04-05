package com.chinarsignals.app.data.repository

import com.chinarsignals.app.data.api.ApiService
import com.chinarsignals.app.data.models.*
import com.chinarsignals.app.utils.Resource
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class SignalRepository @Inject constructor(
    private val apiService: ApiService
) {

    fun getSignals(
        pair: String? = null,
        timeframe: String? = null,
        signalType: String? = null,
        page: Int = 1
    ): Flow<Resource<List<Signal>>> = flow {
        emit(Resource.Loading())
        try {
            val response = apiService.getSignals(pair, timeframe, signalType, page)
            if (response.isSuccessful && response.body()?.success == true) {
                emit(Resource.Success(response.body()!!.data ?: emptyList()))
            } else {
                emit(Resource.Error(response.body()?.message ?: "Failed to load signals"))
            }
        } catch (e: Exception) {
            emit(Resource.Error(e.message ?: "Network error"))
        }
    }

    fun analyzeSignal(symbol: String, timeframe: String): Flow<Resource<Signal>> = flow {
        emit(Resource.Loading())
        try {
            val response = apiService.analyzeSignal(AnalyzeRequest(symbol, timeframe))
            if (response.isSuccessful && response.body()?.success == true) {
                emit(Resource.Success(response.body()!!.data!!))
            } else {
                emit(Resource.Error(response.body()?.message ?: "Analysis failed. Please try again."))
            }
        } catch (e: Exception) {
            emit(Resource.Error(e.message ?: "Network error"))
        }
    }

    fun getSignalById(id: Int): Flow<Resource<Signal>> = flow {
        emit(Resource.Loading())
        try {
            val response = apiService.getSignalById(id)
            if (response.isSuccessful && response.body()?.success == true) {
                emit(Resource.Success(response.body()!!.data!!))
            } else {
                emit(Resource.Error(response.body()?.message ?: "Signal not found"))
            }
        } catch (e: Exception) {
            emit(Resource.Error(e.message ?: "Network error"))
        }
    }

    fun getTodayStats(): Flow<Resource<TodayStats>> = flow {
        emit(Resource.Loading())
        try {
            val response = apiService.getTodayStats()
            if (response.isSuccessful && response.body()?.success == true) {
                emit(Resource.Success(response.body()!!.data!!))
            } else {
                emit(Resource.Error(response.body()?.message ?: "Failed to load stats"))
            }
        } catch (e: Exception) {
            emit(Resource.Error(e.message ?: "Network error"))
        }
    }

    fun markSignalStatus(id: Int, status: String): Flow<Resource<Signal>> = flow {
        emit(Resource.Loading())
        try {
            val response = apiService.markSignalStatus(id, mapOf("status" to status))
            if (response.isSuccessful && response.body()?.success == true) {
                emit(Resource.Success(response.body()!!.data!!))
            } else {
                emit(Resource.Error(response.body()?.message ?: "Failed to update status"))
            }
        } catch (e: Exception) {
            emit(Resource.Error(e.message ?: "Network error"))
        }
    }

    fun getPairs(): Flow<Resource<List<TradingPair>>> = flow {
        emit(Resource.Loading())
        try {
            val response = apiService.getPairs()
            if (response.isSuccessful && response.body()?.success == true) {
                emit(Resource.Success(response.body()!!.data ?: emptyList()))
            } else {
                emit(Resource.Error(response.body()?.message ?: "Failed to load pairs"))
            }
        } catch (e: Exception) {
            emit(Resource.Error(e.message ?: "Network error"))
        }
    }
}
