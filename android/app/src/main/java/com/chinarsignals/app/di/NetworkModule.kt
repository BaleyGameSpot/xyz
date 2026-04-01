package com.chinarsignals.app.di

import com.chinarsignals.app.data.api.ApiClient
import com.chinarsignals.app.data.api.ApiService
import com.chinarsignals.app.data.api.AuthInterceptor
import com.chinarsignals.app.data.api.TokenAuthenticator
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import okhttp3.OkHttpClient
import retrofit2.Retrofit
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object NetworkModule {

    @Provides
    @Singleton
    fun provideOkHttpClient(
        authInterceptor: AuthInterceptor,
        tokenAuthenticator: TokenAuthenticator
    ): OkHttpClient =
        ApiClient.provideOkHttpClient(authInterceptor, tokenAuthenticator)

    @Provides
    @Singleton
    fun provideRetrofit(okHttpClient: OkHttpClient): Retrofit =
        ApiClient.provideRetrofit(okHttpClient)

    @Provides
    @Singleton
    fun provideApiService(retrofit: Retrofit): ApiService =
        ApiClient.provideApiService(retrofit)
}
