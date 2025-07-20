import json
import boto3

dynamodb = boto3.resource('dynamodb')
batch = boto3.client('batch')

def handler(event, context):
    for record in event['Records']:
        body = json.loads(record['body'])
        # Process feedback (e.g., update memory in DynamoDB)
        table = dynamodb.Table('vortex-ai-engine-user-memory')
        table.update_item(
            Key={'user_id': body['user_id']},
            UpdateExpression='SET feedback = :f',
            ExpressionAttributeValues={':f': body['feedback']}
        )
        # Trigger Batch job for retraining
        batch.submit_job(
            jobName='model-retrain',
            jobQueue='vortex-ai-engine-trainer-queue',
            jobDefinition='vortex-ai-engine-trainer-job',
            containerOverrides={'environment': [{'name': 'FEEDBACK_DATA', 'value': json.dumps(body)}]}
        )
    return {'statusCode': 200} 