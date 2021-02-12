'use strict';

const rp = require('request-promise');
const Q = require('q');
const ffmpeg = require('fluent-ffmpeg');
const streamBuffers = require('stream-buffers');;
const express = require('express');;
const app = express();
const alreadyRequested = [];

//  dashSet: the URL to the DASH set
// segment number: the DASH segment ID of which to get a screenshot
// response: The express "res" object to send a response
function getScreenshot(dashSet, segmentNumber, response) {
  const dashInit = dashSet + '.dash';
  const m4sExt = '.m4s';

  if (alreadyRequested.indexOf() > -1) {
    return;
  }

  const urls = [dashInit, dashSet + '-' + segmentNumber + m4sExt];
  function getUrls(urls) {
    const promiseArray = [];

    urls.forEach((url) => {
      const deferred = Q.defer();
      const requestOptions = {
        uri: url,
        encoding: null
      };
      rp(requestOptions).then((buffer) => {
        deferred.resolve(buffer);
        alreadyRequested.push(segmentNumber);
      }).catch((err) => {
        console.log('request error', err);
      });
      promiseArray.push(deferred.promise);
    });

    return Q.all(promiseArray);
  }

  getUrls(urls).then((res) => {
    const videoSegmentBuffer = Buffer.concat(res);

    const videoSegmentReadable = new streamBuffers.ReadableStreamBuffer({
      frequency: 10,
      chunkSize: 2048
    });

    videoSegmentReadable.put(videoSegmentBuffer);
    ffmpeg()
      .input(videoSegmentReadable)
      .output(segmentNumber + '.png')
      .noAudio()
      .seek('0:00')
      .outputOptions('-frames:v 1')
      .on('error', (err) => {
        console.log('ffmpeg error', err);
      })
      .on('end', () => {
        // response.status(201).send('Created');
      })
      .run();
  });
}

app.get('/timestamp/:timestamp', (req, res) => {
  console.log('Getting screenshot for timestamp', req.params.timestamp);
  const segmentNumber = Math.floor(req.params.timestamp / 8);
  getScreenshot(segmentNumber);
  res.status(202).send(segmentNumber);
});

app.listen(3000, () => {
  console.log('Timestamp Generator listening on 3000!');
});

