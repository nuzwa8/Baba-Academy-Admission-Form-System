<?php
// includes/CourseManager.php

class CourseManager {
    // Admin Controlled Fixed Course Fees (Data source placeholder)
    private static $courses = [
        [
            'id' => 'web_dev',
            'name_en' => 'Web Development',
            'name_ur' => 'ویب ڈیولپمنٹ',
            'fee_amount' => 50000 // PKR
        ],
        [
            'id' => 'mobile_app',
            'name_en' => 'Mobile App Development',
            'name_ur' => 'موبائل ایپ ڈیولپمنٹ',
            'fee_amount' => 75000 // PKR
        ],
        [
            'id' => 'graphic_design',
            'name_en' => 'Graphic Design',
            'name_ur' => 'گرافک ڈیزائن',
            'fee_amount' => 30000 // PKR
        ]
    ];

    /**
     * Get all available courses.
     * @return array
     */
    public static function getAllCourses() {
        return self::$courses;
    }

    /**
     * Get course details by ID.
     * @param string $id
     * @return array|null
     */
    public static function getCourseById($id) {
        foreach (self::$courses as $course) {
            if ($course['id'] === $id) {
                return $course;
            }
        }
        return null;
    }
}
