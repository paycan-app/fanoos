# RFM Setup Wizard Improvements

## Overview
The setup wizard has been completely overhauled with advanced RFM segmentation features, interactive visualizations, and a better user experience.

---

## What's New

### 🎯 Advanced RFM Segmentation Options

#### Number of Segments
Choose the granularity that fits your business needs:

- **3 Segments (Simple)**
  - High Value
  - Medium Value
  - Low Value

- **5 Segments (Standard)** - Recommended
  - Champions
  - Loyal
  - Potential
  - At Risk
  - Need Attention

- **11 Segments (Advanced)**
  - Champions
  - Loyal Customers
  - Potential Loyalist
  - New Customers
  - Promising
  - Need Attention
  - About To Sleep
  - At Risk
  - Cannot Lose Them
  - Hibernating
  - Lost

#### Analysis Timeframes
Select the time window for RFM calculations:

- **Last 90 Days** - Quarter analysis
- **Last 6 Months** - Semi-annual view
- **Last Year** - Annual analysis (default)
- **Last 2 Years** - Long-term trends
- **Last 5 Years** - Complete history

### 📊 Interactive Plotly Charts

The results step now features three professional, interactive charts:

1. **Pie Chart** - Customer distribution by segment with percentages
2. **Bar Chart** - Customer count comparison across segments
3. **Multi-Axis Metrics Chart** - RFM metrics visualized side-by-side

**Chart Features:**
- Interactive hover tooltips
- Dark mode support
- Responsive design
- Downloadable as images
- Zoom and pan capabilities

### 📁 Example CSV Files

Download ready-to-use example files from the wizard:
- `customers_example.csv`
- `products_example.csv`
- `orders_example.csv`
- `order_items_example.csv`

Located in: `/public/examples/`

### 🔄 Improved Import Flow

**Step 1: Import CSVs**
- Download example files with one click
- Import in the correct order (Customers → Products → Orders → Order Items)
- Color-coded import buttons
- Real-time progress tracking

**Step 2: RFM Settings**
- Configure number of segments
- Select analysis timeframe
- Adjust quantile bins (2-9)
- Save settings before calculation

**Step 3: Calculate & Review**
- Click "Calculate Segments" to run analysis
- View interactive charts immediately
- Explore detailed statistics table
- Export or share results

---

## Technical Improvements

### Reusable RFM Service (`app/Services/RfmService.php`)

A comprehensive service class that can be used throughout the application:

```php
// Example usage
$rfmService = app(RfmService::class);
$stats = $rfmService->calculateSegments();

// Custom timeframe
$stats = $rfmService->calculateSegments(timeframeDays: 180);

// Get current segment stats
$stats = $rfmService->getSegmentStats();
```

**Key Features:**
- Timeframe-aware calculations
- Quantile-based scoring
- Support for 3/5/11 segment models
- Handles edge cases gracefully
- Returns structured statistics

### Database Enhancements

**New Settings:**
```php
// app/Settings/GeneralSettings.php
public int $rfm_segments;        // 3, 5, or 11
public int $rfm_timeframe_days;  // Analysis window
```

**Migration Applied:**
- `database/settings/2025_11_08_000002_add_rfm_advanced_settings.php`
- Default segments: 5
- Default timeframe: 365 days

### Fixed Issues

✅ **Filament v4 Compatibility**
- Updated all deprecated class imports
- Form components use correct namespaces
- Layout components properly structured

✅ **Import Validation**
- Consistent field mapping
- Proper data type casting
- Foreign key validation
- Example files match schema

✅ **Code Quality**
- Formatted with Laravel Pint
- Type hints throughout
- PSR-12 compliant
- No unused imports

---

## How to Use

### Initial Setup

1. Navigate to **Setup Wizard** in your Filament admin panel

2. **Import Your Data:**
   - Download example CSV files
   - Format your data to match the examples
   - Import in order: Customers → Products → Orders → Order Items
   - Monitor progress in real-time

3. **Configure RFM Settings:**
   - Enable RFM segmentation
   - Choose segment count (3, 5, or 11)
   - Select timeframe (90, 180, 365, 730, or 1825 days)
   - Adjust bins if needed (default: 5)
   - Click "Save RFM Settings"

4. **Calculate & Review:**
   - Click "Calculate Segments"
   - Explore interactive charts
   - Review detailed statistics
   - Download charts if needed

### Re-running Analysis

You can recalculate segments anytime:
- Change settings in Step 2
- Save new configuration
- Go to Step 3 and click "Calculate Segments"
- Previous segments will be updated

---

## File Structure

### New/Modified Files

```
app/
├── Filament/Pages/
│   └── SetupWizard.php                 ✨ Enhanced
├── Services/
│   └── RfmService.php                  🆕 New
└── Settings/
    └── GeneralSettings.php             ✨ Enhanced

database/settings/
└── 2025_11_08_000002_add_rfm_advanced_settings.php  🆕 New

public/examples/
├── customers_example.csv               🆕 New
├── products_example.csv                🆕 New
├── orders_example.csv                  🆕 New
└── order_items_example.csv             🆕 New

resources/views/filament/pages/
├── rfm-results.blade.php               🆕 New (replaces segment-stats)
├── setup-wizard-run-calc.blade.php     ✓ Unchanged
└── setup-wizard-save-settings.blade.php ✓ Unchanged
```

---

## Dependencies

### Already Installed
- **Filament v4** - Admin panel framework
- **Laravel 12** - Application framework
- **Spatie Laravel Settings** - Settings management

### New External Library
- **Plotly.js v2.27.0** - Loaded via CDN for interactive charts

---

## Future Enhancements

Potential improvements for consideration:

1. **Historical Tracking**
   - Store segment changes over time
   - Trend analysis
   - Segment migration charts

2. **Export Features**
   - Export segment data as CSV
   - PDF reports with charts
   - Email automated reports

3. **Advanced Analytics**
   - Customer lifetime value (CLV)
   - Churn prediction
   - Segment-specific recommendations

4. **Automation**
   - Scheduled recalculation
   - Automatic email campaigns per segment
   - Alert for segment changes

---

## Support

For issues or questions:
1. Check example CSV files for correct format
2. Ensure all imports complete successfully
3. Verify settings are saved before calculating
4. Check browser console for JavaScript errors

---

## Credits

- **RFM Analysis**: Industry-standard customer segmentation
- **Plotly.js**: Interactive charting library
- **Filament**: Laravel admin panel framework
