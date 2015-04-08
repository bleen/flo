<?php

namespace flo\Test\Command\PullRequest;

use flo\Test;
use flo\Console\Application;
use Github;

class PullRequestTestHelper extends Test\FunctionalFramework {

  /**
   * The main flo application.
   *
   * @var string
   */
  protected $application;

  /**
   * set up test environment filesystem.
   */
  public function setUp() {
    $this->application = new Application();
    parent::setUp();
  }

  protected function getMockLabelApi($expected, $method = 'post') {
    $httpClientMock = $this->getMock('Guzzle\Http\Client', array('send'));
    $httpClientMock
    ->expects($this->any())
    ->method('send');

    $mock = $this->getMock('Github\HttpClient\HttpClient', array(), array(array(), $httpClientMock));
    $client = new Github\Client($mock);
    $client->setHttpClient($mock);

      // Mock the Issue API.
    $IssueMock = $this->getMockBuilder('Github\Api\Issue')
    ->setMethods(array('labels'))
    ->setConstructorArgs(array($client))
    ->getMock();

      // Mock the label API.
    $labelsMock = $this->getMockBuilder('Github\Api\Issue\Labels')
    ->setMethods(array('get', 'post', 'postRaw', 'patch', 'delete', 'put', 'head'))
    ->setConstructorArgs(array($client))
    ->getMock();

      // This actually runs an assert and makes sure our API call actually returns that :-O.
    $labelsMock->expects($this->once())
    ->method($method)
    ->with($expected)
    ->will($this->returnValue('Success'));

      // Set up the Issue API to return the Label api.
    $IssueMock->expects($this->once())
    ->method('labels')
    ->willReturn($labelsMock);

    return $IssueMock;
  }

  protected function getMockReleaseslApi($expected = '', $method = 'post') {
    $httpClientMock = $this->getMock('Guzzle\Http\Client', array('send'));
    $httpClientMock
      ->expects($this->any())
      ->method('send');

    $mock = $this->getMock('Github\HttpClient\HttpClient', array(), array(array(), $httpClientMock));
    $client = new Github\Client($mock);
    $client->setHttpClient($mock);

    // Mock the Issue API.
    $repoMock = $this->getMockBuilder('Github\Api\Repo')
      ->setMethods(array('releases'))
      ->setConstructorArgs(array($client))
      ->getMock();

    // Mock the label API.
    $releasesMock = $this->getMockBuilder('Github\Api\Repository\Releases')
      ->setMethods(array('all', 'show', 'showTag', 'create', 'edit', 'remove', 'assets'))
      ->setConstructorArgs(array($client))
      ->getMock();

    // This actually runs an assert and makes sure our API call actually returns that :-O.
    $releasesMock->method($method)
      ->will($this->returnValue($expected));

    // Set up the Issue API to return the Label api.
    $repoMock->method('releases')
      ->willReturn($releasesMock);

    return $repoMock;
  }

  protected function getMockBadReleaseslApi($method = 'post') {
    $httpClientMock = $this->getMock('Guzzle\Http\Client', array('send'));
    $httpClientMock
      ->expects($this->any())
      ->method('send');

    $mock = $this->getMock('Github\HttpClient\HttpClient', array(), array(array(), $httpClientMock));
    $client = new Github\Client($mock);
    $client->setHttpClient($mock);

    // Mock the Issue API.
    $repoMock = $this->getMockBuilder('Github\Api\Repo')
      ->setMethods(array('releases'))
      ->setConstructorArgs(array($client))
      ->getMock();

    // Mock the label API.
    $releasesMock = $this->getMockBuilder('Github\Api\Repository\Releases')
      ->setMethods(array('all', 'show', 'showTag', 'create', 'edit', 'remove', 'assets'))
      ->setConstructorArgs(array($client))
      ->getMock();

    $releasesMock->method('showTag')
      ->will($this->throwException(new \Exception));

    // This actually runs an assert and makes sure our API call actually returns that :-O.
    $repoMock->method($method)
      ->will($this->returnValue('Success'));

    // Set up the Issue API to return the Label api.
    $repoMock->expects($this->exactly(1))
      ->method('releases')
      ->willReturn($releasesMock);

    return $repoMock;
  }
}
