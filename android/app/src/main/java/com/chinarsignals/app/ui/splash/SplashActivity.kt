package com.chinarsignals.app.ui.splash

import android.annotation.SuppressLint
import android.content.Intent
import android.os.Bundle
import android.view.animation.AnimationUtils
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.chinarsignals.app.R
import com.chinarsignals.app.data.local.PreferenceManager
import com.chinarsignals.app.databinding.ActivitySplashBinding
import com.chinarsignals.app.ui.auth.AuthActivity
import com.chinarsignals.app.ui.main.MainActivity
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import javax.inject.Inject

@SuppressLint("CustomSplashScreen")
@AndroidEntryPoint
class SplashActivity : AppCompatActivity() {

    private lateinit var binding: ActivitySplashBinding

    @Inject
    lateinit var preferenceManager: PreferenceManager

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivitySplashBinding.inflate(layoutInflater)
        setContentView(binding.root)

        startAnimations()
        navigateAfterDelay()
    }

    private fun startAnimations() {
        val fadeIn = AnimationUtils.loadAnimation(this, R.anim.fade_in)
        val slideUp = AnimationUtils.loadAnimation(this, R.anim.slide_up)

        binding.ivLogo.startAnimation(fadeIn)
        binding.tvAppName.startAnimation(slideUp)
        binding.tvTagline.startAnimation(slideUp)
    }

    private fun navigateAfterDelay() {
        lifecycleScope.launch {
            delay(2500)
            val destination = if (preferenceManager.isLoggedIn()) {
                MainActivity::class.java
            } else {
                AuthActivity::class.java
            }
            startActivity(Intent(this@SplashActivity, destination))
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out)
            finish()
        }
    }
}
