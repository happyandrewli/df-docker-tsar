<?php

/**
 * LICENSE: The MIT License (the "License")
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * https://github.com/azure/azure-storage-php/LICENSE
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * PHP version 5
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Tests\Unit\Common
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Tests\unit\Common;

use MicrosoftAzure\Storage\Common\ServiceException;
use MicrosoftAzure\Storage\Tests\framework\TestResources;

/**
 * Unit tests for class ServiceException
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Tests\Unit\Common
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class ServiceExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers MicrosoftAzure\Storage\Common\ServiceException::__construct
     */
    public function testConstruct()
    {
        // Setup
        $response = TestResources::getFailedResponse(400, 'test info');
        
        // Test
        $e = new ServiceException($response);
        
        // Assert
        $this->assertEquals(400, $e->getCode());
        $this->assertEquals('test info', $e->getErrorText());
        $this->assertEquals($response, $e->getResponse());
    }
    
    /**
     * @covers MicrosoftAzure\Storage\Common\ServiceException::getErrorText
     * @covers MicrosoftAzure\Storage\Common\ServiceException::__construct
     */
    public function testGetErrorText()
    {
        // Setup
        $response = TestResources::getFailedResponse(210, 'test info');
        $e = new ServiceException($response);
        
        // Test
        $actualError = $e->getErrorText();
        // Assert
        $this->assertEquals('test info', $actualError);
    }
    
    /**
     * @covers MicrosoftAzure\Storage\Common\ServiceException::getErrorMessage
     * @covers MicrosoftAzure\Storage\Common\ServiceException::__construct
     * @covers MicrosoftAzure\Storage\Common\ServiceException::parseErrorMessage
     */
    public function testGetErrorMessage()
    {
        // Setup
        $response = TestResources::getFailedResponse(210, 'test info');
        $e = new ServiceException($response);

        // Test
        $actualErrorMessage = $e->getErrorMessage();
        
        // Assert
        $this->assertEquals($actualErrorMessage, TestResources::ERROR_MESSAGE);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\ServiceException::getRequestID
     * @covers MicrosoftAzure\Storage\Common\ServiceException::__construct
     */
    public function testGetRequestID()
    {
        // Setup
        $response = TestResources::getFailedResponse(210, 'test info');
        $e = new ServiceException($response);

        // Assert
        $this->assertEquals($e->getRequestID(), TestResources::REQUEST_ID1);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\ServiceException::getDate
     * @covers MicrosoftAzure\Storage\Common\ServiceException::__construct
     */
    public function testGetDate()
    {
        // Setup
        $response = TestResources::getFailedResponse(210, 'test info');
        $e = new ServiceException($response);

        // Assert
        $this->assertEquals($e->getDate(), TestResources::DATE1);
    }

    /**
     * @covers MicrosoftAzure\Storage\Common\ServiceException::getResponse
     * @covers MicrosoftAzure\Storage\Common\ServiceException::__construct
     */
    public function testGetResponse()
    {
        // Setup
        $response = TestResources::getFailedResponse(210, 'test info');
        $e = new ServiceException($response);

        // Assert
        $this->assertEquals($e->getResponse(), $response);
    }
}
