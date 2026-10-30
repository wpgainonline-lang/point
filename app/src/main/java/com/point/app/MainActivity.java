package com.point.app;

import android.app.Activity;
import android.content.SharedPreferences;
import android.graphics.Color;
import android.graphics.Typeface;
import android.os.Bundle;
import android.view.Gravity;
import android.view.View;
import android.widget.Button;
import android.widget.LinearLayout;
import android.widget.TextView;

public class MainActivity extends Activity {
    private SharedPreferences prefs;
    private TextView pointsView;
    private int points;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        prefs = getSharedPreferences("point", MODE_PRIVATE);
        points = prefs.getInt("points", 0);

        LinearLayout root = new LinearLayout(this);
        root.setOrientation(LinearLayout.VERTICAL);
        root.setGravity(Gravity.CENTER);
        root.setPadding(48, 48, 48, 48);
        root.setBackgroundColor(Color.parseColor("#F5F7FA"));

        TextView title = new TextView(this);
        title.setText("Point");
        title.setTextSize(32);
        title.setTypeface(Typeface.DEFAULT_BOLD);
        title.setTextColor(Color.parseColor("#1A237E"));
        title.setGravity(Gravity.CENTER);
        root.addView(title);

        pointsView = new TextView(this);
        pointsView.setTextSize(88);
        pointsView.setTypeface(Typeface.DEFAULT_BOLD);
        pointsView.setTextColor(Color.parseColor("#283593"));
        pointsView.setGravity(Gravity.CENTER);
        root.addView(pointsView);

        root.addView(button("+1 Point", Color.parseColor("#2E7D32"), v -> change(1)));
        root.addView(button("-1 Point", Color.parseColor("#C62828"), v -> change(-1)));
        root.addView(button("Reset", Color.parseColor("#546E7A"), v -> { points = 0; save(); }));

        setContentView(root);
        render();
    }

    private Button button(String text, int color, View.OnClickListener l) {
        Button b = new Button(this);
        b.setText(text);
        b.setTextColor(Color.WHITE);
        b.setBackgroundColor(color);
        b.setOnClickListener(l);
        LinearLayout.LayoutParams lp = new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT, 160);
        lp.topMargin = 32;
        b.setLayoutParams(lp);
        return b;
    }

    private void change(int d) { points = Math.max(0, points + d); save(); }

    private void save() { prefs.edit().putInt("points", points).apply(); render(); }

    private void render() { pointsView.setText(String.valueOf(points)); }
}
