<!DOCTYPE html>
<html>
<body>
<h2>Alert Recovered: {{ $rule->name }}</h2>
<p>The alert rule <strong>{{ $rule->name }}</strong> has recovered and is no longer triggering.</p>
<ul>
    <li>Metric: {{ $rule->metric }}</li>
    <li>Current value: {{ $value }}</li>
    <li>Threshold: {{ $rule->operator }} {{ $rule->threshold }}</li>
    <li>Window: {{ $rule->window_minutes }} minutes</li>
</ul>
</body>
</html>
