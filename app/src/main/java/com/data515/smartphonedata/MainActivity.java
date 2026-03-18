package com.data515.smartphonedata;

import android.content.Intent;
import android.os.Bundle;
import android.widget.Button;
import androidx.appcompat.app.AppCompatActivity;

public class MainActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        Button btnAddData = findViewById(R.id.btnAddData);
        Button btnViewData = findViewById(R.id.btnViewData);

        btnAddData.setOnClickListener(v ->
            startActivity(new Intent(this, InputActivity.class))
        );

        btnViewData.setOnClickListener(v ->
            startActivity(new Intent(this, ViewDataActivity.class))
        );
    }
}
