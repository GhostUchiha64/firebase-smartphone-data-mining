package com.data515.smartphonedata;

import android.os.Bundle;
import android.text.TextUtils;
import android.widget.*;
import androidx.appcompat.app.AppCompatActivity;
import com.google.firebase.database.*;

public class InputActivity extends AppCompatActivity {

    private EditText etPrice, etDisplay, etMemory, etResolution;
    private RadioGroup rgCondition;
    private TextView tvStatus;
    private DatabaseReference dbRef;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_input);

        if (getSupportActionBar() != null) {
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
            getSupportActionBar().setTitle("Add Smartphone Data");
        }

        dbRef = FirebaseDatabase.getInstance().getReference("smartphones");

        etPrice      = findViewById(R.id.etPrice);
        etDisplay    = findViewById(R.id.etDisplay);
        etMemory     = findViewById(R.id.etMemory);
        etResolution = findViewById(R.id.etResolution);
        rgCondition  = findViewById(R.id.rgCondition);
        tvStatus     = findViewById(R.id.tvStatus);

        Button btnSave  = findViewById(R.id.btnSave);
        Button btnClear = findViewById(R.id.btnClear);

        btnSave.setOnClickListener(v -> saveData());
        btnClear.setOnClickListener(v -> clearForm());
    }

    private void saveData() {
        String priceStr  = etPrice.getText().toString().trim();
        String dispStr   = etDisplay.getText().toString().trim();
        String memStr    = etMemory.getText().toString().trim();
        String resStr    = etResolution.getText().toString().trim();

        if (TextUtils.isEmpty(priceStr) || TextUtils.isEmpty(dispStr) ||
            TextUtils.isEmpty(memStr)  || TextUtils.isEmpty(resStr)) {
            tvStatus.setText("Please fill in all fields.");
            tvStatus.setTextColor(0xFFE74C3C);
            return;
        }

        int selectedId = rgCondition.getCheckedRadioButtonId();
        if (selectedId == -1) {
            tvStatus.setText("Please select a condition.");
            tvStatus.setTextColor(0xFFE74C3C);
            return;
        }

        RadioButton rb = findViewById(selectedId);
        String condition = rb.getText().toString();

        double price       = Double.parseDouble(priceStr);
        double displaySize = Double.parseDouble(dispStr);
        int    memory      = Integer.parseInt(memStr);
        int    resolution  = Integer.parseInt(resStr);

        Smartphone phone = new Smartphone(price, displaySize, memory, resolution, condition);

        tvStatus.setText("Saving...");
        tvStatus.setTextColor(0xFF4A9EFF);

        dbRef.push().setValue(phone)
            .addOnSuccessListener(unused -> {
                tvStatus.setText("✓ Saved successfully!");
                tvStatus.setTextColor(0xFF27AE60);
                clearForm();
            })
            .addOnFailureListener(e -> {
                tvStatus.setText("Error: " + e.getMessage());
                tvStatus.setTextColor(0xFFE74C3C);
            });
    }

    private void clearForm() {
        etPrice.setText("");
        etDisplay.setText("");
        etMemory.setText("");
        etResolution.setText("");
        rgCondition.clearCheck();
    }

    @Override
    public boolean onSupportNavigateUp() {
        finish();
        return true;
    }
}
