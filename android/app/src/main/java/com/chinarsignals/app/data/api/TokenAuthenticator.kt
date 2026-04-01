package com.chinarsignals.app.data.api

import android.util.Log
import com.chinarsignals.app.BuildConfig
import com.chinarsignals.app.data.local.PreferenceManager
import com.google.gson.Gson
import com.google.gson.JsonObject
import okhttp3.Authenticator
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody
import okhttp3.Response
import okhttp3.Route
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class TokenAuthenticator @Inject constructor(
    private val preferenceManager: PreferenceManager
) : Authenticator {

    // Separate client without this authenticator to avoid recursion
    private val refreshClient = OkHttpClient.Builder().build()
    private val gson = Gson()

    override fun authenticate(route: Route?, response: Response): Request? {
        // Prevent infinite retry loops (already tried once → give up)
        if (responseCount(response) >= 2) {
            preferenceManager.clearAuth()
            return null
        }

        val currentToken = preferenceManager.getAuthToken() ?: return null

        synchronized(this) {
            // Another thread may have already refreshed — use the latest token
            val latestToken = preferenceManager.getAuthToken()
            if (latestToken != null && latestToken != currentToken) {
                return response.request.newBuilder()
                    .header("Authorization", "Bearer $latestToken")
                    .build()
            }

            val newToken = tryRefresh(currentToken)
            return if (newToken != null) {
                preferenceManager.saveAuthToken(newToken)
                Log.d("TokenAuthenticator", "Token refreshed successfully")
                response.request.newBuilder()
                    .header("Authorization", "Bearer $newToken")
                    .build()
            } else {
                Log.w("TokenAuthenticator", "Token refresh failed — clearing auth")
                preferenceManager.clearAuth()
                null
            }
        }
    }

    private fun tryRefresh(expiredToken: String): String? {
        return try {
            val request = Request.Builder()
                .url("${BuildConfig.BASE_URL}api/auth/refresh")
                .post(RequestBody.create(null, ByteArray(0)))
                .header("Accept", "application/json")
                .header("Authorization", "Bearer $expiredToken")
                .build()

            val resp = refreshClient.newCall(request).execute()
            if (resp.isSuccessful) {
                val body = resp.body?.string() ?: return null
                val json = gson.fromJson(body, JsonObject::class.java)
                json?.getAsJsonObject("data")?.get("token")?.asString
            } else {
                null
            }
        } catch (e: Exception) {
            Log.e("TokenAuthenticator", "Refresh request failed: ${e.message}")
            null
        }
    }

    private fun responseCount(response: Response): Int {
        var count = 1
        var prior = response.priorResponse
        while (prior != null) {
            count++
            prior = prior.priorResponse
        }
        return count
    }
}
