# Firebase-Based Mobile Data Mining System
### CSCI 515 – Exercise II | University of North Dakota

A full-stack data mining application that collects smartphone market data via an Android app, stores it in Firebase Realtime Database, and presents analytics through a live PHP web dashboard.

---

## Live Demo

**Web Dashboard:** http://undcemcs02.und.edu/~siddartha.bandi/515/2/

---

## Project Overview

This project demonstrates a complete data pipeline:

1. **Data Collection** — Android app lets users log smartphone listings with price, condition, display size, memory, and resolution
2. **Cloud Storage** — Data is stored in real-time in Firebase Realtime Database
3. **Web Analytics** — PHP web dashboard reads from Firebase and presents statistics, predictions, and loss analysis

---

## Tech Stack

| Layer | Technology |
|---|---|
| Mobile App | Android (Java), Firebase Realtime Database |
| Backend | Firebase Realtime Database (NoSQL) |
| Web Frontend | PHP, HTML, CSS, Bootstrap |
| Server | University Linux Server (Apache) |
| Build System | Gradle 8.6.1, Android Gradle Plugin 8.6.1 |

---

## Features

### Android App
- Input form for smartphone data: price, condition, display, memory, resolution
- Saves records to Firebase in real-time
- View all saved records in a RecyclerView list
- Pull-to-refresh support
- Dark-themed Material UI

### Web Dashboard (6 pages)
- **Home (`index.php`)** — Summary stats and recent entries
- **JSON View (`json.php`)** — Raw Firebase data in formatted JSON
- **Price Predictor (`predict.php`)** — Predicts smartphone price based on specs
- **Single Record (`single.php`)** — Detailed view of individual records
- **Loss Analysis (`loss.php`)** — Depreciation and market loss calculations
- **Config (`config.php`)** — Firebase connection and session authentication

---

## Project Structure

```
firebase-smartphone-data-mining/
│
├── android_app/                    # Android Studio project
│   ├── app/
│   │   ├── src/main/
│   │   │   ├── java/com/data515/smartphonedata/
│   │   │   │   ├── MainActivity.java
│   │   │   │   ├── ViewDataActivity.java
│   │   │   │   └── SmartphoneAdapter.java
│   │   │   └── res/
│   │   │       ├── layout/
│   │   │       ├── values/
│   │   │       └── drawable/
│   │   └── build.gradle
│   ├── build.gradle
│   └── settings.gradle
│
├── web/                            # PHP web dashboard
│   ├── config.php                  # Firebase config & auth (use config.example.php as template)
│   ├── index.php                   # Home / summary
│   ├── json.php                    # JSON data view
│   ├── predict.php                 # Price predictor
│   ├── single.php                  # Single record view
│   └── loss.php                    # Loss analysis
│
├── config.example.php              # Template for config.php (safe to commit)
├── .gitignore
└── README.md
```

---

## Setup Instructions

### Android App

1. Clone the repo and open the `android_app/` folder in Android Studio
2. Add your own `google-services.json` to `android_app/app/` (download from Firebase Console)
3. Sync Gradle and run on an emulator or device (minSdk 24, targetSdk 34)

> **Note:** `google-services.json` is excluded from this repo for security. You must add your own Firebase project credentials.

### Web Dashboard

1. Copy the PHP files to your web server
2. Copy `config.example.php` to `config.php`
3. Fill in your Firebase project URL and set your own session password
4. Access the site through your server's public URL

---

## Firebase Database Structure

```json
{
  "smartphones": {
    "-UniqueKey123": {
      "price": 450,
      "condition": "Excellent",
      "display": 6.1,
      "memory": 128,
      "resolution": "2532x1170",
      "timestamp": 1710000000000
    }
  }
}
```

---

## Security Notes

- `google-services.json` is excluded via `.gitignore` — never commit this file
- `config.php` is excluded via `.gitignore` — use `config.example.php` as the template
- Firebase database rules should be set to authenticated access in production
- The web dashboard is password-protected — the password is not documented publicly and should be shared privately

---

## Author

**Siddartha Bandi**
GitHub: [@GhostUchiha64](https://github.com/GhostUchiha64)

---

## License

This project was built for academic purposes as part of CSCI 515 coursework at the University of North Dakota.
