package com.data515.smartphonedata;

import android.os.Bundle;
import android.view.View;
import android.widget.*;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;
import androidx.annotation.NonNull;
import com.google.firebase.database.*;
import java.util.*;

public class ViewDataActivity extends AppCompatActivity {

    private RecyclerView recyclerView;
    private SmartphoneAdapter adapter;
    private List<Smartphone> phoneList;
    private ProgressBar progressBar;
    private TextView tvEmpty, tvCount;
    private SwipeRefreshLayout swipeRefresh;
    private DatabaseReference dbRef;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_view_data);

        if (getSupportActionBar() != null) {
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
            getSupportActionBar().setTitle("Smartphone Records");
        }

        dbRef        = FirebaseDatabase.getInstance().getReference("smartphones");
        progressBar  = findViewById(R.id.progressBar);
        tvEmpty      = findViewById(R.id.tvEmpty);
        tvCount      = findViewById(R.id.tvCount);
        swipeRefresh = findViewById(R.id.swipeRefresh);
        recyclerView = findViewById(R.id.recyclerView);

        phoneList = new ArrayList<>();
        adapter   = new SmartphoneAdapter(phoneList);
        recyclerView.setLayoutManager(new LinearLayoutManager(this));
        recyclerView.setAdapter(adapter);

        swipeRefresh.setColorSchemeColors(0xFF4A9EFF);
        swipeRefresh.setOnRefreshListener(this::loadData);

        loadData();
    }

    private void loadData() {
        progressBar.setVisibility(View.VISIBLE);
        tvEmpty.setVisibility(View.GONE);

        dbRef.addListenerForSingleValueEvent(new ValueEventListener() {
            @Override
            public void onDataChange(@NonNull DataSnapshot snapshot) {
                phoneList.clear();
                for (DataSnapshot child : snapshot.getChildren()) {
                    Smartphone s = child.getValue(Smartphone.class);
                    if (s != null) {
                        s.setKey(child.getKey());
                        phoneList.add(s);
                    }
                }
                // Newest first
                Collections.sort(phoneList, (a, b) ->
                    Long.compare(b.getTimestamp(), a.getTimestamp()));

                adapter.notifyDataSetChanged();
                progressBar.setVisibility(View.GONE);
                swipeRefresh.setRefreshing(false);

                int count = phoneList.size();
                tvCount.setText(count + " record" + (count == 1 ? "" : "s"));
                tvEmpty.setVisibility(count == 0 ? View.VISIBLE : View.GONE);
            }

            @Override
            public void onCancelled(@NonNull DatabaseError error) {
                progressBar.setVisibility(View.GONE);
                swipeRefresh.setRefreshing(false);
                tvEmpty.setText("Error loading data: " + error.getMessage());
                tvEmpty.setVisibility(View.VISIBLE);
            }
        });
    }

    @Override
    public boolean onSupportNavigateUp() {
        finish();
        return true;
    }
}
