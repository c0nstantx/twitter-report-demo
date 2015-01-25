<?php

namespace Rocket\Models;

class Renderer
{
    /**
     * Render a simple HTML form for twitter handle post
     * @return string
     */
    public function renderHandleForm()
    {
        $form = "
        <h1>You are authorized</h1>
        <form method='get'>
        <legend for='handle'>Enter Handle:</legend>
        <input type='text' name='handle' id='handle' />
        <input type='submit' value='Show Report' />
        </form>
        ";
        return $this->render($form);
    }

    public function renderReport($tweetData)
    {
        if (isset($tweetData->errors)) {
            return $this->renderError($tweetData->errors[0]->message);
        }
        $text = "";
        $text .= "<img src='{$tweetData['banner']->url}' height='{$tweetData['banner']->h}' width='{$tweetData['banner']->w}' />";
        $text .= "<h1>Latest Tweets</h1>
        <ul>";
        foreach ($tweetData['tweets'] as $tweet) {
            $text .= "<li>{$tweet->text}</li>";
        }
        $text .= "</ul>";
        return $this->render($text);
    }

    /**
     * Render an error message
     * @param  string $errorMessage
     * @return string
     */
    public function renderError($errorMessage)
    {
        $message = "<div class='error'>$errorMessage</div>";
        return $this->render($message);
    }

    /**
     * Render HTML output
     * @param  string $html
     * @return string
     */
    public function render($html)
    {
        return "
        <!DOCTYPE html>
        <html>
            <head>
            <title>Twitter Report Demo</title>
            </head>
            <body>
            $html
            </body>
        </html>
        ";
    }
}