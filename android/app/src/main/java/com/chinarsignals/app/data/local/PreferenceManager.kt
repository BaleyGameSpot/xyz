package com.chinarsignals.app.data.local

import android.content.Context
import android.content.SharedPreferences
import com.chinarsignals.app.data.models.User
import com.google.gson.Gson
import dagger.hilt.android.qualifiers.ApplicationContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class PreferenceManager @Inject constructor(
    @ApplicationContext private val context: Context
) {
    private val prefs: SharedPreferences = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
    private val gson = Gson()

    companion object {
        private const val PREF_NAME = "chinar_signals_prefs"
        private const val KEY_AUTH_TOKEN = "auth_token"
        private const val KEY_USER = "user_data"
        private const val KEY_SUBSCRIPTION_TYPE = "subscription_type"
        private const val KEY_ONBOARDING_DONE = "onboarding_done"
        private const val KEY_FCM_TOKEN = "fcm_token"
        private const val KEY_SELECTED_PAIR_ID = "selected_pair_id"
        private const val KEY_SELECTED_TIMEFRAME = "selected_timeframe"
    }

    fun saveAuthToken(token: String) {
        prefs.edit().putString(KEY_AUTH_TOKEN, token).apply()
    }

    fun getAuthToken(): String? = prefs.getString(KEY_AUTH_TOKEN, null)

    fun isLoggedIn(): Boolean = !getAuthToken().isNullOrBlank()

    fun saveUser(user: User) {
        prefs.edit().putString(KEY_USER, gson.toJson(user)).apply()
    }

    fun getUser(): User? {
        val json = prefs.getString(KEY_USER, null) ?: return null
        return try {
            gson.fromJson(json, User::class.java)
        } catch (e: Exception) {
            null
        }
    }

    fun saveSubscriptionType(type: String) {
        prefs.edit().putString(KEY_SUBSCRIPTION_TYPE, type).apply()
    }

    fun getSubscriptionType(): String = prefs.getString(KEY_SUBSCRIPTION_TYPE, "none") ?: "none"

    fun isSubscribed(): Boolean {
        val type = getSubscriptionType()
        return type != "none" && type.isNotBlank()
    }

    fun saveFcmToken(token: String) {
        prefs.edit().putString(KEY_FCM_TOKEN, token).apply()
    }

    fun getFcmToken(): String? = prefs.getString(KEY_FCM_TOKEN, null)

    fun setOnboardingDone(done: Boolean) {
        prefs.edit().putBoolean(KEY_ONBOARDING_DONE, done).apply()
    }

    fun isOnboardingDone(): Boolean = prefs.getBoolean(KEY_ONBOARDING_DONE, false)

    fun saveSelectedPairId(pairId: Int) {
        prefs.edit().putInt(KEY_SELECTED_PAIR_ID, pairId).apply()
    }

    fun getSelectedPairId(): Int = prefs.getInt(KEY_SELECTED_PAIR_ID, -1)

    fun saveSelectedTimeframe(timeframe: String) {
        prefs.edit().putString(KEY_SELECTED_TIMEFRAME, timeframe).apply()
    }

    fun getSelectedTimeframe(): String = prefs.getString(KEY_SELECTED_TIMEFRAME, "1H") ?: "1H"

    fun clearAll() {
        prefs.edit().clear().apply()
    }

    fun clearAuth() {
        prefs.edit()
            .remove(KEY_AUTH_TOKEN)
            .remove(KEY_USER)
            .remove(KEY_SUBSCRIPTION_TYPE)
            .apply()
    }
}
