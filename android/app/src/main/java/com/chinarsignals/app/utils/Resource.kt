package com.chinarsignals.app.utils

sealed class Resource<out T> {
    data class Success<T>(val data: T) : Resource<T>()
    data class Error(val message: String, val code: Int? = null) : Resource<Nothing>()
    class Loading<T> : Resource<T>()
}
