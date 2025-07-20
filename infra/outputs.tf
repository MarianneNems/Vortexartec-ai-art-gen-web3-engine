output "feedback_topic_arn" {
  value = aws_sns_topic.feedback_topic.arn
}

output "feedback_queue_url" {
  value = aws_sqs_queue.feedback_queue.url
}

output "user_memory_table" {
  value = aws_dynamodb_table.user_memory.name
}

output "public_outputs_bucket" {
  value = aws_s3_bucket.public_outputs.bucket
}

output "continuous_trainer_arn" {
  value = aws_lambda_function.continuous_trainer.arn
}

output "trainer_job_queue_arn" {
  value = aws_batch_job_queue.trainer_queue.arn
} 