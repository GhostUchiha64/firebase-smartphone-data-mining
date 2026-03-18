package com.data515.smartphonedata;

import android.content.res.ColorStateList;
import android.view.*;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import java.text.SimpleDateFormat;
import java.util.*;

public class SmartphoneAdapter extends RecyclerView.Adapter<SmartphoneAdapter.ViewHolder> {

    private final List<Smartphone> items;
    private static final SimpleDateFormat SDF =
        new SimpleDateFormat("MMM d, yyyy  h:mm a", Locale.US);

    public SmartphoneAdapter(List<Smartphone> items) {
        this.items = items;
    }

    @NonNull @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View v = LayoutInflater.from(parent.getContext())
                     .inflate(R.layout.item_smartphone, parent, false);
        return new ViewHolder(v);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder h, int pos) {
        Smartphone s = items.get(pos);
        h.tvPrice.setText(String.format(Locale.US, "$%.2f", s.getPrice()));
        h.tvDisplay.setText(String.format(Locale.US, "%.1f\"", s.getDisplaySize()));
        h.tvMemory.setText(s.getMemory() + " GB");
        h.tvResolution.setText(s.getResolution() + " MP");
        h.tvCondition.setText(s.getCondition());
        h.tvTimestamp.setText(SDF.format(new Date(s.getTimestamp())));

        int color;
        switch (s.getCondition()) {
            case "New":        color = 0xFF27AE60; break;
            case "Excellent":  color = 0xFF4A9EFF; break;
            case "Very good":  color = 0xFF8E44AD; break;
            default:           color = 0xFFE67E22; break;
        }
        h.tvCondition.setBackgroundTintList(ColorStateList.valueOf(color));
    }

    @Override public int getItemCount() { return items.size(); }

    static class ViewHolder extends RecyclerView.ViewHolder {
        TextView tvPrice, tvDisplay, tvMemory, tvResolution, tvCondition, tvTimestamp;
        ViewHolder(View v) {
            super(v);
            tvPrice     = v.findViewById(R.id.tvPrice);
            tvDisplay   = v.findViewById(R.id.tvDisplay);
            tvMemory    = v.findViewById(R.id.tvMemory);
            tvResolution= v.findViewById(R.id.tvResolution);
            tvCondition = v.findViewById(R.id.tvCondition);
            tvTimestamp = v.findViewById(R.id.tvTimestamp);
        }
    }
}
