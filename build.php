<?php

require_once 'vendor/autoload.php';

use Aws\Ec2\Ec2Client;
use Aws\Ec2\Exception\Ec2Exception;

define('INSTANCE_IMAGE_ID', 'ami-08935252a36e25f85');
define('INSTANCE_TYPE', 'm3.large');
define('KEY_PAIR_NAME', 'datashaman');
define('SECURITY_GROUP_NAME', 'datashaman');
define('SECURITY_GROUP_DESCRIPTION', 'datashaman');

class Builder
{
    private Ec2Client $client;

    private $securityGroup;
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

        // register_shutdown_function([$this, 'shutdown']);
    }

    public function build()
    {
        $this->createKeyPairIfNeeded();
        $this->createSecurityGroupIfNeeded();
        $this->runInstance();
    }

    public function shutdown()
    {
        $this->terminateInstance();
        $this->deleteSecurityGroup();
    }

    protected function createSecurityGroupIfNeeded()
    {
        $groupName = SECURITY_GROUP_NAME;

        try {
            $result = $this->client->createSecurityGroup(
                [
                    'Description' => SECURITY_GROUP_DESCRIPTION,
                    'GroupName' => $groupName,
                ]
            );

            $this->securityGroup = $result->toArray();
            $groupId = $this->securityGroup['GroupId'];

            $this->client->authorizeSecurityGroupIngress(
                [
                    'GroupId' => $groupId,
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
            echo("Created security group: $groupId\n");
        } catch (Ec2Exception $exception) {
            if ($exception->getAwsErrorMessage() === "The security group '$groupName' already exists") {
                $result = $this->client->describeSecurityGroups(
                    [
                        'GroupNames' => [SECURITY_GROUP_NAME],
                    ]
                );

                $groups = $result->get('SecurityGroups');
                $this->securityGroup = $groups[0];
                $groupId = $this->securityGroup['GroupId'];
                echo("Found existing security group: $groupId\n");
            } else {
                throw $exception;
            }
        }
    }

    protected function getKeyPairPath()
    {
        $keyName = KEY_PAIR_NAME;

        return getenv('HOME') . "/.ssh/$keyName.pem";
    }

    protected function createKeyPairIfNeeded()
    {
        $keyName = KEY_PAIR_NAME;

        try {
            $result = $this->client->createKeyPair(
                [
                    'KeyName' => $keyName,
                    'Query' => 'KeyMaterial',
                    'Output' => 'text',
                ]
            );

            $keyPair = $result->toArray();
            $filename = $this->getKeyPairPath();

            if (file_exists($filename)) {
                echo "Found existing PEM private key, aborting\n";
                exit(1);
            }

            file_put_contents(
                $filename,
                $keyPair['KeyMaterial']
            );

            chmod($filename, 0600);

            echo("Saved PEM private key: {$filename}\n");
            echo("Created key pair: {$keyPair['KeyPairId']}\n");
        } catch (Ec2Exception $exception) {
            if ($exception->getAwsErrorMessage() === "The keypair '$keyName' already exists.") {
                echo("Found existing key pair\n");
            } else {
                throw $exception;
            }
        }
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
        $instanceId = $this->instance['InstanceId'];

        echo ("Pending instance: $instanceId\n");

        do {
            sleep(5);

            $result = $this->client->describeInstances(
                [
                    'InstanceIds' => [$instanceId],
                ]
            );

            $reservations = $result->get('Reservations');
        } while (
            $reservations
            && $reservations[0]['Instances']
            && $reservations[0]['Instances'][0]['State']['Name'] !== 'running'
        );

        echo ("Running instance: $instanceId\n");
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
        echo ("Terminating instance: {$instance['InstanceId']}\n");

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

        echo ("Terminated instance: {$instance['InstanceId']}\n");
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
