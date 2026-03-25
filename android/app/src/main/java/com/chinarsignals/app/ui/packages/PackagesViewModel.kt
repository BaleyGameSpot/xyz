package com.chinarsignals.app.ui.packages

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.chinarsignals.app.data.models.Package
import com.chinarsignals.app.data.repository.SubscriptionRepository
import com.chinarsignals.app.utils.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.launchIn
import kotlinx.coroutines.flow.onEach
import javax.inject.Inject

@HiltViewModel
class PackagesViewModel @Inject constructor(
    private val subscriptionRepository: SubscriptionRepository
) : ViewModel() {

    private val _packagesState = MutableStateFlow<Resource<List<Package>>?>(null)
    val packagesState: StateFlow<Resource<List<Package>>?> = _packagesState

    init {
        loadPackages()
    }

    fun loadPackages() {
        subscriptionRepository.getPackages()
            .onEach { _packagesState.value = it }
            .launchIn(viewModelScope)
    }
}
