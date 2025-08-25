<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Edit Blog Post</title>
</head>
<body>
<div class="container">
    <h1>Edit Blog Post</h1>
    <p>Placeholder edit form.</p>
    @isset($blog)
        <pre>@json($blog, JSON_PRETTY_PRINT)</pre>
    @endisset
</div>
</body>
</html>
