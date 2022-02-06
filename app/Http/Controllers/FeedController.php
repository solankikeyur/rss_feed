<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Feed;

class FeedController extends Controller
{

    private $url = "http://rss.cnn.com/rss/cnn_topstories.rss";
    private $noFeedMsg = "No feeds found.";



    public function index()
    {
        try {

            $feedData = $this->getRssFeedData();
            if(empty($feedData)) {
                return response()->json(['message' => "No feeds found."]);
            }
            return response()->json(['title' => isset($feedData['title']) ? $feedData['title'] : "", "", "feeds" => isset($feedData['item']) ? $feedData['item'] : "No feeds found."]);
        } catch(\Exception $e) {
            return response()->json(["message" =>$e->getMessage()]);
        }
    }

    public function search(Request $request)
    {
        try {

            $enterKeywordMsg = "Enter keyword to search.";

            $keyword = $request->keyword;
            $searchData = [];

            if(empty($keyword)) {
                return response()->json(['message' => $enterKeywordMsg]);
            }

            //search for particular keyword in db and return that if found
            $dbFeed = $this->searchInDb($keyword);
            if(!empty($dbFeed)) {
                $dbContent = $dbFeed->content;
                return response()->json(["channel" => $dbFeed->channel, "feeds" => $dbContent, "source" => "DATABASE"]);
            }

            //fetch and read rss feed
            $feedData = $this->getRssFeedData();
            if(empty($feedData)) {
                return response()->json(["message" => $this->noFeedMsg]);
            }

            $channelTitle = isset($feedData['title']) && !empty($feedData['title']) ? $feedData['title'] : [];
            $channelItems = isset($feedData['item']) && !empty($feedData['item']) ? $feedData['item'] : [];
            if(empty($channelItems)) {
                return response()->json(["message" => $this->noFeedMsg]);
            }

            //search for keyword in title
            foreach($channelItems as $key => $item) {
                if(isset($item['title']) && !empty($item['title'])) {
                   
                    if(Str::contains($item['title'], $keyword)) {
                        $searchData[] = $item;
                    }

                }
            }

            if(empty($searchData)) {
                return response()->json(["message" => $this->noFeedMsg]);
            }

            //create new record for keyword searched
            Feed::create(["keyword" => $keyword,"channel" => $channelTitle, "content" => json_encode($searchData, JSON_UNESCAPED_SLASHES)]);

            return response()->json(["channel" => $channelTitle, "feeds" => $searchData, "source" => "RSS FEED URL"]);
            
        } catch(\Exception $e) {
            return response()->json(["message" =>$this->noFeedMsg]);
        }

        
    }

    private function getRssFeedData()
    {
        try {

            $feedData = [];
            if(empty($this->url)) {
                return $feedData;
            }

            //get content from url
            $feedData = file_get_contents($this->url);
            if(empty($feedData)) {
                return response()->json(['message' => $enterKeywordMsg]);
            }

            //reading xml content and coverts that to array
            $feedData = simplexml_load_string($feedData, "SimpleXMLElement", LIBXML_NOCDATA);
            $feedData = json_encode($feedData);
            $feedData = json_decode($feedData,TRUE);
            if(empty($feedData)) {
                return [];
            }   

            $channelData = isset($feedData['channel']) && !empty($feedData['channel']) ? $feedData['channel'] : [];

            $feedData['title'] = isset($channelData['title']) && !empty($channelData['title']) ? $channelData['title'] : [];
            $feedData['item'] = isset($channelData['item']) && !empty($channelData['item']) ? $channelData['item'] : [];

            return $feedData;

        } catch(\Exception $e) {
            throw new \Exception($e->getMessage(), 1);
            
        }
        
    }

    private function searchInDb($keyword)
    {
        $feed = Feed::where(\DB::raw('BINARY `keyword`'), $keyword)->first();
        return $feed;
    }
}
