package com.data515.smartphonedata;

public class Smartphone {
    private String key;
    private double price;
    private double displaySize;
    private int memory;
    private int resolution;
    private String condition;
    private long timestamp;

    // Required empty constructor for Firebase
    public Smartphone() {}

    public Smartphone(double price, double displaySize, int memory, int resolution, String condition) {
        this.price = price;
        this.displaySize = displaySize;
        this.memory = memory;
        this.resolution = resolution;
        this.condition = condition;
        this.timestamp = System.currentTimeMillis();
    }

    public String getKey() { return key; }
    public void setKey(String key) { this.key = key; }

    public double getPrice() { return price; }
    public void setPrice(double price) { this.price = price; }

    public double getDisplaySize() { return displaySize; }
    public void setDisplaySize(double displaySize) { this.displaySize = displaySize; }

    public int getMemory() { return memory; }
    public void setMemory(int memory) { this.memory = memory; }

    public int getResolution() { return resolution; }
    public void setResolution(int resolution) { this.resolution = resolution; }

    public String getCondition() { return condition; }
    public void setCondition(String condition) { this.condition = condition; }

    public long getTimestamp() { return timestamp; }
    public void setTimestamp(long timestamp) { this.timestamp = timestamp; }
}
