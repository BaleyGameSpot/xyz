package com.chinarsignals.app.ui.main.signals

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.chinarsignals.app.data.models.Signal
import com.chinarsignals.app.data.repository.SignalRepository
import com.chinarsignals.app.utils.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.launchIn
import kotlinx.coroutines.flow.onEach
import javax.inject.Inject

@HiltViewModel
class SignalsViewModel @Inject constructor(
    private val signalRepository: SignalRepository
) : ViewModel() {

    private val _signalsState = MutableStateFlow<Resource<List<Signal>>?>(null)
    val signalsState: StateFlow<Resource<List<Signal>>?> = _signalsState

    private val _filterPair = MutableStateFlow<String?>(null)
    private val _filterTimeframe = MutableStateFlow<String?>(null)
    private val _filterType = MutableStateFlow<String?>(null)

    init {
        loadSignals()
    }

    fun loadSignals() {
        signalRepository.getSignals(
            pair = _filterPair.value,
            timeframe = _filterTimeframe.value,
            signalType = _filterType.value
        ).onEach { _signalsState.value = it }
            .launchIn(viewModelScope)
    }

    fun setFilterPair(pair: String?) {
        _filterPair.value = pair
        loadSignals()
    }

    fun setFilterTimeframe(timeframe: String?) {
        _filterTimeframe.value = timeframe
        loadSignals()
    }

    fun setFilterType(type: String?) {
        _filterType.value = type
        loadSignals()
    }

    fun clearFilters() {
        _filterPair.value = null
        _filterTimeframe.value = null
        _filterType.value = null
        loadSignals()
    }
}
