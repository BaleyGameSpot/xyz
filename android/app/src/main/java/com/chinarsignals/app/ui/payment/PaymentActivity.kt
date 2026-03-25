package com.chinarsignals.app.ui.payment

import android.content.Intent
import android.graphics.Bitmap
import android.os.Bundle
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.chinarsignals.app.R
import com.chinarsignals.app.data.models.Package
import com.chinarsignals.app.databinding.ActivityPaymentBinding
import com.chinarsignals.app.ui.main.MainActivity
import com.chinarsignals.app.utils.Constants
import com.chinarsignals.app.utils.Resource
import com.chinarsignals.app.utils.copyToClipboard
import com.chinarsignals.app.utils.gone
import com.chinarsignals.app.utils.snackbarError
import com.chinarsignals.app.utils.toast
import com.chinarsignals.app.utils.visible
import com.google.zxing.BarcodeFormat
import com.google.zxing.EncodeHintType
import com.google.zxing.qrcode.QRCodeWriter
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch

@AndroidEntryPoint
class PaymentActivity : AppCompatActivity() {

    private lateinit var binding: ActivityPaymentBinding
    private val viewModel: PaymentViewModel by viewModels()
    private var selectedPackage: Package? = null
    private var walletAddress: String = ""

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityPaymentBinding.inflate(layoutInflater)
        setContentView(binding.root)

        setSupportActionBar(binding.toolbar)
        supportActionBar?.setDisplayHomeAsUpEnabled(true)
        supportActionBar?.title = "Complete Payment"

        selectedPackage = intent.getParcelableExtra(Constants.EXTRA_PACKAGE)
        if (selectedPackage == null) {
            finish()
            return
        }

        bindPackageInfo()
        setupClickListeners()
        observeData()

        // Initiate payment to get wallet address
        viewModel.initiatePayment(selectedPackage!!.id)
    }

    private fun bindPackageInfo() {
        val pkg = selectedPackage!!
        binding.tvPackageName.text = pkg.name
        val price = pkg.promoPrice ?: pkg.price
        binding.tvAmount.text = "$${String.format("%.2f", price)} USDT"
        binding.tvPackageDetail.text = "${pkg.durationDays} days access • ${pkg.dailySignalsLimit} signals/day"
    }

    private fun setupClickListeners() {
        binding.btnCopyAddress.setOnClickListener {
            if (walletAddress.isNotBlank()) {
                copyToClipboard("Wallet Address", walletAddress)
                toast("Address copied to clipboard!")
            }
        }

        binding.btnSubmitPayment.setOnClickListener {
            val txHash = binding.etTxHash.text.toString().trim()
            if (txHash.length < 10) {
                binding.tilTxHash.error = "Please enter a valid transaction hash"
                return@setOnClickListener
            }
            binding.tilTxHash.error = null
            viewModel.verifyPayment(selectedPackage!!.id, txHash)
        }
    }

    private fun observeData() {
        lifecycleScope.launch {
            viewModel.walletState.collectLatest { resource ->
                when (resource) {
                    is Resource.Loading -> {
                        binding.progressBar.visible()
                        binding.cardWalletInfo.gone()
                    }
                    is Resource.Success -> {
                        binding.progressBar.gone()
                        binding.cardWalletInfo.visible()
                        val wallet = resource.data
                        walletAddress = wallet.address
                        binding.tvWalletAddress.text = wallet.address
                        binding.tvNetwork.text = "${wallet.currency} (${wallet.network})"
                        binding.tvSendAmount.text = "${wallet.amount} ${wallet.currency}"
                        generateQrCode(wallet.address)
                    }
                    is Resource.Error -> {
                        binding.progressBar.gone()
                        binding.root.snackbarError(resource.message)
                    }
                    null -> {}
                }
            }
        }

        lifecycleScope.launch {
            viewModel.verifyState.collectLatest { resource ->
                when (resource) {
                    is Resource.Loading -> {
                        binding.btnSubmitPayment.isEnabled = false
                        binding.btnSubmitPayment.text = "Verifying..."
                    }
                    is Resource.Success -> {
                        binding.btnSubmitPayment.isEnabled = true
                        binding.btnSubmitPayment.text = "Payment Verified!"
                        val intent = Intent(this@PaymentActivity, MainActivity::class.java).apply {
                            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
                        }
                        startActivity(intent)
                    }
                    is Resource.Error -> {
                        binding.btnSubmitPayment.isEnabled = true
                        binding.btnSubmitPayment.text = "I've Sent the Payment"
                        binding.root.snackbarError(resource.message)
                        viewModel.resetVerifyState()
                    }
                    null -> {
                        binding.btnSubmitPayment.isEnabled = true
                        binding.btnSubmitPayment.text = "I've Sent the Payment"
                    }
                }
            }
        }
    }

    private fun generateQrCode(content: String) {
        try {
            val hints = mapOf(EncodeHintType.MARGIN to 1)
            val writer = QRCodeWriter()
            val bitMatrix = writer.encode(content, BarcodeFormat.QR_CODE, 512, 512, hints)
            val width = bitMatrix.width
            val height = bitMatrix.height
            val bitmap = Bitmap.createBitmap(width, height, Bitmap.Config.RGB_565)
            val bgColor = getColor(R.color.bg_card)
            val fgColor = getColor(R.color.accent_green)
            for (x in 0 until width) {
                for (y in 0 until height) {
                    bitmap.setPixel(x, y, if (bitMatrix[x, y]) fgColor else bgColor)
                }
            }
            binding.ivQrCode.setImageBitmap(bitmap)
        } catch (e: Exception) {
            binding.ivQrCode.gone()
        }
    }

    override fun onSupportNavigateUp(): Boolean {
        onBackPressedDispatcher.onBackPressed()
        return true
    }
}
