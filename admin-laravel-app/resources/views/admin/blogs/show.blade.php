<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>View Blog Post</title>
</head>
<body>
<div class="container">
    <h1>Blog Details</h1>
    @isset($blog)
        <pre>@json($blog, JSON_PRETTY_PRINT)</pre>
    @else
        <p>No blog data provided.</p>
    @endisset
</div>
</body>
</html>
