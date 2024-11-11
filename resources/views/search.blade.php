<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Documents</title>
</head>

<body>

    <form action="/upload-to-solr" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file" accept=".pdf,.docx,.txt,.jpg,.png,.csv">
        <button type="submit">Upload File</button>
    </form>

    <h1>Search Documents</h1>
    <!-- Search form with date range inputs -->
    <form action="{{ route('search') }}" method="GET">
        <input type="text" name="query" placeholder="Search..." value="{{ request('query') }}"><br><br>

        <label for="start_date">Start Date:</label>
        <input type="date" name="start_date" value="{{ request('start_date') }}"><br><br>

        <label for="end_date">End Date:</label>
        <input type="date" name="end_date" value="{{ request('end_date') }}"><br><br>

        <button type="submit">Search</button>
    </form>

    @if (isset($results))
        <h2>Search Results</h2>
        <ul>
            @foreach ($results->response->docs as $doc)
                <li>
                    <strong>{{ @$doc->id }}</strong><br>
                    <strong>{{ @$doc->title[0] }}</strong><br>
                    {{ @$doc->content[0] }}<br>
                    <em>{{ @$doc->author[0] }} | {{ @$doc->date[0] }}</em>
                </li>
            @endforeach
        </ul>
    @endif
</body>

</html>
