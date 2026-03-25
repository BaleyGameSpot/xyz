package com.chinarsignals.app.ui.main.profile

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.chinarsignals.app.data.models.SubscriptionStatus
import com.chinarsignals.app.data.models.User
import com.chinarsignals.app.data.repository.AuthRepository
import com.chinarsignals.app.data.repository.SubscriptionRepository
import com.chinarsignals.app.utils.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.launchIn
import kotlinx.coroutines.flow.onEach
import javax.inject.Inject

@HiltViewModel
class ProfileViewModel @Inject constructor(
    private val authRepository: AuthRepository,
    private val subscriptionRepository: SubscriptionRepository
) : ViewModel() {

    private val _userState = MutableStateFlow<Resource<User>?>(null)
    val userState: StateFlow<Resource<User>?> = _userState

    private val _subscriptionState = MutableStateFlow<Resource<SubscriptionStatus>?>(null)
    val subscriptionState: StateFlow<Resource<SubscriptionStatus>?> = _subscriptionState

    private val _logoutState = MutableStateFlow(false)
    val logoutState: StateFlow<Boolean> = _logoutState

    init {
        loadUserInfo()
        loadSubscription()
    }

    fun loadUserInfo() {
        authRepository.getMe()
            .onEach { _userState.value = it }
            .launchIn(viewModelScope)
    }

    fun loadSubscription() {
        subscriptionRepository.getSubscriptionStatus()
            .onEach { _subscriptionState.value = it }
            .launchIn(viewModelScope)
    }

    fun getCachedUser() = authRepository.getCachedUser()

    fun logout() {
        authRepository.logout()
        _logoutState.value = true
    }
}
