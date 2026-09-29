USE student_service_management;

ALTER TABLE complaints
    DROP FOREIGN KEY complaints_ibfk_4,
    MODIFY department_id INT NULL,
    ADD CONSTRAINT fk_complaints_department
        FOREIGN KEY (department_id) REFERENCES departments (department_id)
        ON DELETE SET NULL;