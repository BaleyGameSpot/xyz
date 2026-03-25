package com.chinarsignals.app.ui.payment

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.chinarsignals.app.data.models.SubscriptionStatus
import com.chinarsignals.app.data.models.WalletInfo
import com.chinarsignals.app.data.repository.SubscriptionRepository
import com.chinarsignals.app.utils.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.launchIn
import kotlinx.coroutines.flow.onEach
import javax.inject.Inject

@HiltViewModel
class PaymentViewModel @Inject constructor(
    private val subscriptionRepository: SubscriptionRepository
) : ViewModel() {

    private val _walletState = MutableStateFlow<Resource<WalletInfo>?>(null)
    val walletState: StateFlow<Resource<WalletInfo>?> = _walletState

    private val _verifyState = MutableStateFlow<Resource<SubscriptionStatus>?>(null)
    val verifyState: StateFlow<Resource<SubscriptionStatus>?> = _verifyState

    fun initiatePayment(packageId: Int) {
        subscriptionRepository.purchaseSubscription(packageId)
            .onEach { _walletState.value = it }
            .launchIn(viewModelScope)
    }

    fun verifyPayment(packageId: Int, txHash: String) {
        subscriptionRepository.verifyPayment(packageId, txHash)
            .onEach { _verifyState.value = it }
            .launchIn(viewModelScope)
    }

    fun resetVerifyState() {
        _verifyState.value = null
    }
}
