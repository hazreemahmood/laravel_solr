<?php

namespace App\Http\Controllers;

use Solarium\Client;
use Solarium\Core\Client\Adapter\Curl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SearchController extends Controller
{
    protected $client;

    public function __construct()
    {
        // Configure the adapter
        $adapter = new Curl();
        // Configure the adapter
        $eventDispatcher = new EventDispatcher();

        // Configuration array for Solarium client
        $config = [
            'endpoint' => [
                'localhost' => [
                    'host' => config('solr.host'),
                    'port' => config('solr.port'),
                    'path' => config('solr.path'),
                    'core' => config('solr.core')
                ],
            ],
        ];

        // Initialize the Solarium client with both adapter and config
        $this->client = new Client($adapter, $eventDispatcher, $config);
    }

    public function search(Request $request)
    {
        // Get the search query from the user input
        $query = $request->input('query');
        $startDate = $request->input('start_date');  // Expecting date format yyyy-MM-dd
        $endDate = $request->input('end_date');      // Expecting date format yyyy-MM-dd

        // Create a Solarium query object (select query)
        $selectQuery = $this->client->createSelect();

        // If a query was provided, set it on the Solarium query object
        if ($query) {
            $selectQuery->setQuery("content:$query title:$query");
        }
        // Add date range filter if provided
        if ($startDate && $endDate) {
            // Solr expects dates in ISO format
            $startDate = \Carbon\Carbon::parse($startDate)->toISOString();
            $endDate = \Carbon\Carbon::parse($endDate)->toISOString();

            // Add the date range filter to the query
            $selectQuery->createFilterQuery('date_range')
                ->setQuery('date:[' . $startDate . ' TO ' . $endDate . ']');
        }


        // Pagination: Limit results to 10 and start from the first record
        $selectQuery->setRows(10);
        $selectQuery->setStart(0);

        // dd($this->client);
        // Execute the query
        $result = $this->client->select($selectQuery);
        $response = $result->getResponse();
        $data = json_decode($response->getBody());

        // Extract search results
        $documents = $data->response->docs ?? [];
        $numFound = $data->response->numFound ?? 0;

        // Return the results to the view
        return view('search', [
            'results' => $data,
            'documents' => $documents,
            'numFound' => $numFound
        ]);
    }
}
