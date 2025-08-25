<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin Reports - Dashboard</title>
</head>
<body>
<div class="container">
    <h1>Reports Dashboard</h1>
    <p>This is a placeholder for the admin reports dashboard.</p>

    @isset($quickStats)
        <h2>Quick Stats</h2>
        <pre>@json($quickStats, JSON_PRETTY_PRINT)</pre>
    @endisset

    @isset($recentActivity)
        <h2>Recent Activity</h2>
        <pre>@json($recentActivity, JSON_PRETTY_PRINT)</pre>
    @endisset
</div>
</body>
</html>
