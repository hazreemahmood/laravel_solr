<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SolrController extends Controller
{
    public function uploadFileToSolr(Request $request)
    {
        // Validate the uploaded file
        $request->validate([
            'file' => 'required|file|mimes:pdf,docx,csv,txt,jpg,png'
        ]);
        // Store the file locally (optional)
        $filePath = $request->file('file')->store('uploads');

        // $filePath = $filePath->getRealPath();

        // // Read the CSV file and prepare the data for Solr
        // $csvData = $this->processCsvToSolrFormat($filePath);

        // // Send the data to Solr
        // $response = $this->sendToSolr($csvData);
        // dd($response);

        // Get file path
        $file = storage_path("app/{$filePath}");

        // Create Solr upload request to the extracting request handler (Tika)
        $solrUrl = 'http://' . env('SOLR_HOST') . ':' . env('SOLR_PORT') . env('SOLR_PATH') . env('SOLR_CORE') . '/update?literal.id=' . basename($file);
        // dd($solrUrl);
        // Send the file to Solr using POST request
        $response = Http::withHeaders([
            'Content-Type' => 'application/octet-stream',
        ])->attach(
            'file', file_get_contents($file), basename($file)
        )->post($solrUrl);
        // dd($response);

        // Check if the file was successfully indexed
        if ($response->successful()) {
            return response()->json(['message' => 'File uploaded and indexed successfully!']);
        } else {
            return response()->json(['message' => 'Failed to upload the file.'], 500);
        }

    }
    /**
     * Process the CSV file and convert it to a Solr-friendly format (JSON).
     *
     * @param string $filePath
     * @return array
     */
    private function processCsvToSolrFormat($filePath)
    {
        // Read the CSV file and convert to an array
        $data = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle);  // Get the headers (assumed to be field names)
            while (($row = fgetcsv($handle)) !== false) {
                $data[] = array_combine($headers, $row);
            }
            fclose($handle);
        }

        return $data;
    }

    /**
     * Send the data to Solr using cURL.
     *
     * @param array $data
     * @return void
     */
    private function sendToSolr(array $data)
    {
        $solrUrl = 'http://localhost:8983/solr/documents_core/update?commit=true';
        
        // Prepare the JSON data
        $jsonData = json_encode($data);

        // Send the data using cURL
        $ch = curl_init($solrUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/csv'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

        // Execute the request
        dd($ch);
        $response = curl_exec($ch);
        curl_close($ch);

        // Handle the response (optional)
        // Check if Solr response is successful (this could be logged or checked)
        if ($response === false) {
            // Log or handle failure
            return false;
            Log::error('Error sending data to Solr: ' . curl_error($ch));
        }
        return true;
    }
}
