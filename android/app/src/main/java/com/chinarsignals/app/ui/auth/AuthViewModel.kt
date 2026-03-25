package com.chinarsignals.app.ui.auth

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.chinarsignals.app.data.models.AuthResponse
import com.chinarsignals.app.data.repository.AuthRepository
import com.chinarsignals.app.utils.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharedFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.launchIn
import kotlinx.coroutines.flow.onEach
import javax.inject.Inject

@HiltViewModel
class AuthViewModel @Inject constructor(
    private val authRepository: AuthRepository
) : ViewModel() {

    private val _authState = MutableStateFlow<Resource<AuthResponse>?>(null)
    val authState: StateFlow<Resource<AuthResponse>?> = _authState

    private val _navigateToMain = MutableSharedFlow<Unit>()
    val navigateToMain: SharedFlow<Unit> = _navigateToMain

    fun login(email: String, password: String) {
        authRepository.login(email, password)
            .onEach { _authState.value = it }
            .launchIn(viewModelScope)
    }

    fun register(name: String, email: String, password: String, confirmPassword: String) {
        authRepository.register(name, email, password, confirmPassword)
            .onEach { _authState.value = it }
            .launchIn(viewModelScope)
    }

    fun googleAuth(idToken: String, email: String, name: String, avatar: String?) {
        authRepository.googleAuth(idToken, email, name, avatar)
            .onEach { _authState.value = it }
            .launchIn(viewModelScope)
    }

    fun resetState() {
        _authState.value = null
    }
}
