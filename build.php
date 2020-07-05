<?php

require_once 'vendor/autoload.php';

use Aws\Ec2\Ec2Client;
use Aws\Ec2\Exception\Ec2Exception;

define('INSTANCE_IMAGE_ID', 'ami-08935252a36e25f85');
define('INSTANCE_TYPE', 't1.micro');
define('KEY_PAIR_NAME', 'datashaman');
define('SECURITY_GROUP_NAME', 'datashaman');
define('SECURITY_GROUP_DESCRIPTION', 'datashaman');

class Builder
{
    private Ec2Client $client;

    private $securityGroup;
    private $keyPair;
    private $instance;

    public function __construct()
    {
        $this->client = new Ec2Client(
            [
                'profile' => 'default',
                'region' => 'eu-west-1',
                'version' => '2016-11-15',
            ]
        );
    }

    public function __destruct()
    {
        $this->clean();
    }

    public function build()
    {
        $this->createSecurityGroup();
        $this->createKeyPair();
        $this->runInstance();
    }

    public function clean()
    {
        $this->terminateInstance();
        $this->deleteKeyPair();
        $this->deleteSecurityGroup();
    }

    protected function createSecurityGroup()
    {
        $result = $this->client->createSecurityGroup(
            [
                'Description' => SECURITY_GROUP_DESCRIPTION,
                'GroupName' => SECURITY_GROUP_NAME,
            ]
        );
        $this->securityGroup = $result->toArray();
        $this->client->authorizeSecurityGroupIngress(
            [
                'GroupId' => $this->securityGroup['GroupId'],
                'IpPermissions' => [
                    [
                        'FromPort' => 22,
                        'IpProtocol' => 'tcp',
                        'IpRanges' => [
                            [
                                'CidrIp' => '0.0.0.0/0',
                            ],
                        ],
                        'ToPort' => 22,
                    ]
                ],
            ]
        );
        echo("Created security group: {$this->securityGroup['GroupId']}\n");
    }

    protected function createKeyPair()
    {
        $result = $this->client->createKeyPair(
            [
                'KeyName' => KEY_PAIR_NAME,
                'Query' => 'KeyMaterial',
                'Output' => 'text',
            ]
        );
        $this->keyPair = $result->toArray();
        echo("Created key pair: {$this->keyPair['KeyPairId']}\n");
    }

    protected function runInstance()
    {
        $result = $this->client->runInstances(
            [
                'ImageId' => INSTANCE_IMAGE_ID,
                'InstanceType' => INSTANCE_TYPE,
                'KeyName' => KEY_PAIR_NAME,
                'SecurityGroupIds' => [$this->securityGroup['GroupId']],
                'MaxCount' => 1,
                'MinCount' => 1,
            ]
        );

        $instances = $result->get('Instances');
        $this->instance = $instances[0];

        echo ("Created instance: {$this->instance['InstanceId']}\n");
    }

    protected function terminateInstance()
    {
        if (!$this->instance) {
            return;
        }

        $result = $this->client->terminateInstances(
            [
                'InstanceIds' => [$this->instance['InstanceId']],
            ]
        );

        $instances = $result->get('TerminatingInstances');
        $instance = $instances[0];
        echo ("Deleting instance: {$instance['InstanceId']}\n");

        do {
            sleep(5);

            $result = $this->client->describeInstances(
                [
                    'InstanceIds' => [$this->instance['InstanceId']],
                ]
            );

            $reservations = $result->get('Reservations');
        } while (
            $reservations
            && $reservations[0]['Instances']
            && $reservations[0]['Instances'][0]['State']['Name'] !== 'terminated'
        );

        echo ("Deleted instance: {$instance['InstanceId']}\n");
    }

    protected function deleteKeyPair()
    {
        if (!$this->keyPair) {
            return;
        }

        $keyPairId = $this->keyPair['KeyPairId'];

        $result = $this->client->deleteKeyPair(
            [
                'KeyPairId' => $keyPairId,
            ]
        );

        echo ("Deleted key pair: $keyPairId\n");
    }

    protected function deleteSecurityGroup()
    {
        if (!$this->securityGroup) {
            return;
        }

        $groupId = $this->securityGroup['GroupId'];

        $result = $this->client->deleteSecurityGroup(
            [
                'GroupId' => $groupId,
            ]
        );

        echo ("Deleted security group: $groupId\n");
    }
}

$builder = (new Builder())->build();
