package com.chinarsignals.app.di

import com.chinarsignals.app.data.api.ApiService
import com.chinarsignals.app.data.local.PreferenceManager
import com.chinarsignals.app.data.repository.*
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object RepositoryModule {

    @Provides
    @Singleton
    fun provideAuthRepository(
        apiService: ApiService,
        preferenceManager: PreferenceManager
    ): AuthRepository = AuthRepository(apiService, preferenceManager)

    @Provides
    @Singleton
    fun provideSignalRepository(
        apiService: ApiService
    ): SignalRepository = SignalRepository(apiService)

    @Provides
    @Singleton
    fun provideSubscriptionRepository(
        apiService: ApiService,
        preferenceManager: PreferenceManager
    ): SubscriptionRepository = SubscriptionRepository(apiService, preferenceManager)

    @Provides
    @Singleton
    fun provideUserRepository(
        apiService: ApiService,
        preferenceManager: PreferenceManager
    ): UserRepository = UserRepository(apiService, preferenceManager)
}
