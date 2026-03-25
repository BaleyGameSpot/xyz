package com.chinarsignals.app.ui.main.home

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.chinarsignals.app.data.local.PreferenceManager
import com.chinarsignals.app.data.models.Signal
import com.chinarsignals.app.data.models.TodayStats
import com.chinarsignals.app.data.models.TradingPair
import com.chinarsignals.app.data.repository.SignalRepository
import com.chinarsignals.app.data.repository.SubscriptionRepository
import com.chinarsignals.app.data.models.SubscriptionStatus
import com.chinarsignals.app.utils.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.launchIn
import kotlinx.coroutines.flow.onEach
import javax.inject.Inject

@HiltViewModel
class HomeViewModel @Inject constructor(
    private val signalRepository: SignalRepository,
    private val subscriptionRepository: SubscriptionRepository,
    private val preferenceManager: PreferenceManager
) : ViewModel() {

    private val _pairsState = MutableStateFlow<Resource<List<TradingPair>>?>(null)
    val pairsState: StateFlow<Resource<List<TradingPair>>?> = _pairsState

    private val _statsState = MutableStateFlow<Resource<TodayStats>?>(null)
    val statsState: StateFlow<Resource<TodayStats>?> = _statsState

    private val _analyzeState = MutableStateFlow<Resource<Signal>?>(null)
    val analyzeState: StateFlow<Resource<Signal>?> = _analyzeState

    private val _subscriptionState = MutableStateFlow<Resource<SubscriptionStatus>?>(null)
    val subscriptionState: StateFlow<Resource<SubscriptionStatus>?> = _subscriptionState

    private val _selectedPairId = MutableStateFlow(preferenceManager.getSelectedPairId())
    val selectedPairId: StateFlow<Int> = _selectedPairId

    private val _selectedTimeframe = MutableStateFlow(preferenceManager.getSelectedTimeframe())
    val selectedTimeframe: StateFlow<String> = _selectedTimeframe

    init {
        loadPairs()
        loadTodayStats()
        loadSubscriptionStatus()
    }

    fun loadPairs() {
        signalRepository.getPairs()
            .onEach { _pairsState.value = it }
            .launchIn(viewModelScope)
    }

    fun loadTodayStats() {
        signalRepository.getTodayStats()
            .onEach { _statsState.value = it }
            .launchIn(viewModelScope)
    }

    fun loadSubscriptionStatus() {
        subscriptionRepository.getSubscriptionStatus()
            .onEach { _subscriptionState.value = it }
            .launchIn(viewModelScope)
    }

    fun selectPair(pairId: Int) {
        _selectedPairId.value = pairId
        preferenceManager.saveSelectedPairId(pairId)
    }

    fun selectTimeframe(timeframe: String) {
        _selectedTimeframe.value = timeframe
        preferenceManager.saveSelectedTimeframe(timeframe)
    }

    fun analyzeSignal() {
        val pairId = _selectedPairId.value
        val timeframe = _selectedTimeframe.value
        if (pairId == -1) return
        signalRepository.analyzeSignal(pairId, timeframe)
            .onEach { _analyzeState.value = it }
            .launchIn(viewModelScope)
    }

    fun resetAnalyzeState() {
        _analyzeState.value = null
    }

    fun getCachedUser() = preferenceManager.getUser()

    fun isSubscribed() = preferenceManager.isSubscribed()
}
