package com.chinarsignals.app.ui.packages

import android.content.Intent
import android.os.Bundle
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.chinarsignals.app.databinding.ActivityPackagesBinding
import com.chinarsignals.app.ui.payment.PaymentActivity
import com.chinarsignals.app.utils.Constants
import com.chinarsignals.app.utils.Resource
import com.chinarsignals.app.utils.gone
import com.chinarsignals.app.utils.snackbarError
import com.chinarsignals.app.utils.visible
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch

@AndroidEntryPoint
class PackagesActivity : AppCompatActivity() {

    private lateinit var binding: ActivityPackagesBinding
    private val viewModel: PackagesViewModel by viewModels()
    private lateinit var packageAdapter: PackageAdapter

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityPackagesBinding.inflate(layoutInflater)
        setContentView(binding.root)

        setSupportActionBar(binding.toolbar)
        supportActionBar?.setDisplayHomeAsUpEnabled(true)
        supportActionBar?.title = "Choose Your Plan"

        setupRecyclerView()
        observeData()
    }

    private fun setupRecyclerView() {
        packageAdapter = PackageAdapter { pkg ->
            val intent = Intent(this, PaymentActivity::class.java).apply {
                putExtra(Constants.EXTRA_PACKAGE, pkg)
            }
            startActivity(intent)
        }
        binding.rvPackages.apply {
            layoutManager = LinearLayoutManager(this@PackagesActivity)
            adapter = packageAdapter
        }
    }

    private fun observeData() {
        lifecycleScope.launch {
            viewModel.packagesState.collectLatest { resource ->
                when (resource) {
                    is Resource.Loading -> {
                        binding.progressBar.visible()
                        binding.rvPackages.gone()
                    }
                    is Resource.Success -> {
                        binding.progressBar.gone()
                        binding.rvPackages.visible()
                        packageAdapter.submitList(resource.data)
                    }
                    is Resource.Error -> {
                        binding.progressBar.gone()
                        binding.root.snackbarError(resource.message)
                    }
                    null -> {}
                }
            }
        }
    }

    override fun onSupportNavigateUp(): Boolean {
        onBackPressedDispatcher.onBackPressed()
        return true
    }
}
