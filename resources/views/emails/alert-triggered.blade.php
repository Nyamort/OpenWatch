<!DOCTYPE html>
<html>
<body>
<h2>Alert Triggered: {{ $rule->name }}</h2>
<p>The alert rule <strong>{{ $rule->name }}</strong> has been triggered.</p>
<ul>
    <li>Metric: {{ $rule->metric }}</li>
    <li>Current value: {{ $value }}</li>
    <li>Threshold: {{ $rule->operator }} {{ $rule->threshold }}</li>
    <li>Window: {{ $rule->window_minutes }} minutes</li>
</ul>
</body>
</html>
